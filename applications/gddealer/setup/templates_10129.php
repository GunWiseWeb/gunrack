<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

try
{
	$row = \IPS\Db::i()->select( 'template_content', 'core_theme_templates',
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'front', 'dealers', 'feedSettings' ]
	)->first();
}
catch ( \Throwable ) { $row = ''; }

$body = (string) $row;
if ( $body === '' ) { return; }

/* Strip ANY prior v10128 panel injection so we don't end up with two. */
$body = preg_replace( '#<!--\s*GD_FEED_UPLOAD_PANEL_v10128\s*-->.*?\{\{endif\}\}\s*#s', '', $body );
$body = preg_replace( '#<!--\s*GD_FEED_UPLOAD_PANEL_v10129\s*-->.*?\{\{endif\}\}\s*#s', '', $body );

$marker = '<!-- GD_FEED_UPLOAD_PANEL_v10129 -->';

$uploadPanel = <<<'HTML'
<!-- GD_FEED_UPLOAD_PANEL_v10129 -->
{{if !empty( $data['delivery_mode'] ) && $data['delivery_mode'] === 'manual'}}
<div class="gdPanel" style="margin-bottom:24px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:24px;">
	<div style="margin-bottom:18px;">
		<h2 style="margin:0 0 4px;font-size:16px;font-weight:600;color:#0f172a;">Upload feed file</h2>
		<p style="margin:0;font-size:13px;color:#64748b;">Drop a CSV, XML, or JSON file from your store's export. The system will import it on the next sync cycle (usually within a few minutes).</p>
	</div>

	<form method="post" action="{$data['upload_url']}" enctype="multipart/form-data" style="margin-bottom:0;">
		<input type="hidden" name="MAX_FILE_SIZE" value="52428800">
		<input type="file" name="gddealer_front_feed_file" accept=".csv,.xml,.json,.tsv,.txt" required style="display:block;width:100%;padding:12px;border:1px dashed #cbd5e1;border-radius:6px;background:#f8fafc;font:inherit;cursor:pointer;margin-bottom:12px;">
		<button type="submit" style="padding:9px 18px;background:#1e40af;color:#fff;border:0;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;">Upload feed file</button>
	</form>

	{{if !empty( $data['recent_uploads'] )}}
	<div style="margin-top:24px;padding-top:18px;border-top:1px solid #f1f5f9;">
		<div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">Recent uploads</div>
		<table style="width:100%;border-collapse:collapse;font-size:13px;">
			<thead>
				<tr>
					<th style="text-align:left;padding:6px 8px;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">File</th>
					<th style="text-align:left;padding:6px 8px;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Format</th>
					<th style="text-align:left;padding:6px 8px;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Size</th>
					<th style="text-align:left;padding:6px 8px;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:0.05em;">Uploaded</th>
				</tr>
			</thead>
			<tbody>
				{{foreach $data['recent_uploads'] as $u}}
				<tr style="border-top:1px solid #f1f5f9;">
					<td style="padding:8px;color:#0f172a;font-family:ui-monospace,'SF Mono',Menlo,monospace;font-size:12px;">{$u['file_name']}</td>
					<td style="padding:8px;color:#475569;text-transform:uppercase;font-size:11px;font-weight:600;">{$u['upload_format']}</td>
					<td style="padding:8px;color:#475569;">{expression="number_format( (int) $u['file_size_bytes'] / 1024 ) . ' KB'"}</td>
					<td style="padding:8px;color:#475569;">{$u['uploaded_ago']}</td>
				</tr>
				{{endforeach}}
			</tbody>
		</table>
	</div>
	{{endif}}
</div>
{{endif}}

HTML;

/* Try multiple anchor patterns. v10128 only checked single-quote style;
   IPS template parser can also have stripped/normalized whitespace. */
$injected = false;

$anchors = [
	"{\$data['form']|raw}",
	'{$data["form"]|raw}',
	"{\$data['form'] |raw}",
	"{\$data['form']| raw}",
];

foreach ( $anchors as $anchor )
{
	if ( strpos( $body, $anchor ) !== false )
	{
		$body = str_replace( $anchor, $uploadPanel . "\n" . $anchor, $body );
		$injected = true;
		break;
	}
}

/* Final fallback: regex match on |raw filter applied to anything containing 'form'. */
if ( !$injected )
{
	$body = preg_replace(
		'#(\{\$data\[[\'"]form[\'"]\]\s*\|\s*raw\})#',
		$uploadPanel . "\n$1",
		$body,
		1,
		$count
	);
	if ( $count > 0 ) { $injected = true; }
}

/* Last-resort fallback: prepend to body. Always renders. */
if ( !$injected )
{
	$body = $uploadPanel . "\n" . $body;
}

try
{
	\IPS\Db::i()->update( 'core_theme_templates',
		[
			'template_data'    => '$data',
			'template_content' => $body,
			'template_updated' => time(),
		],
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'front', 'dealers', 'feedSettings' ]
	);
}
catch ( \Throwable ) {}
