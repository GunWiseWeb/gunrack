<?php
/**
 * @brief       GD Dealer Manager — Template sync for v1.0.25 (10025)
 *
 * Seeds the admin/dealers/disputeCounts template that was added in 1.0.24
 * but only defined in setup/install.php (which doesn't run on upgrades).
 * Idempotent: updates the row if present, inserts if not.
 */

namespace IPS\gddealer\setup;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$disputeCountsContent = <<<'TEMPLATE_EOT'
<div class="ipsBox ipsPull">
	<div class="ipsBox_body">
		<h2 class="ipsType_sectionHead" style="padding:16px 20px;margin:0;border-bottom:1px solid var(--i-border-color,#e0e0e0)">Dispute Counts — {$monthKey}</h2>
		<table class="ipsTable ipsTable_zebra" style="width:100%">
			<thead>
				<tr>
					<th>Dealer</th>
					<th>Tier</th>
					<th>Base Limit</th>
					<th>Bonus</th>
					<th>Used</th>
					<th>Remaining</th>
					<th style="text-align:right">Actions</th>
				</tr>
			</thead>
			<tbody>
				{{if count($rows) === 0}}
					<tr><td colspan="7" style="padding:20px;text-align:center;color:#999">No dealers found.</td></tr>
				{{endif}}
				{{foreach $rows as $r}}
					<tr>
						<td>{$r['dealer_name']}</td>
						<td>{$r['tier']}</td>
						<td>{{if $r['unlimited']}}Unlimited{{else}}{$r['limit']}{{endif}}</td>
						<td>{$r['bonus']}</td>
						<td>{$r['used']}</td>
						<td>{{if $r['unlimited']}}Unlimited{{else}}{$r['remaining']}{{endif}}</td>
						<td style="text-align:right;white-space:nowrap">
							<a href="{$r['reset_url']}" class="ipsButton ipsButton--small ipsButton--light" title="Reset count to 0">Reset</a>
							<a href="{$r['grant1_url']}" class="ipsButton ipsButton--small ipsButton--positive" title="Grant 1 bonus dispute">+1</a>
							<a href="{$r['grant5_url']}" class="ipsButton ipsButton--small ipsButton--positive" title="Grant 5 bonus disputes">+5</a>
						</td>
					</tr>
				{{endforeach}}
			</tbody>
		</table>
	</div>
</div>
TEMPLATE_EOT;

try
{
	$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_templates', [
		'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		'gddealer', 'admin', 'dealers', 'disputeCounts',
	] )->first();

	if ( $exists > 0 )
	{
		\IPS\Db::i()->update( 'core_theme_templates', [
			'template_data'    => '$rows, $monthKey',
			'template_content' => $disputeCountsContent,
		], [
			'template_app=? AND template_location=? AND template_group=? AND template_name=?',
			'gddealer', 'admin', 'dealers', 'disputeCounts',
		] );
	}
	else
	{
		\IPS\Db::i()->insert( 'core_theme_templates', [
			'template_set_id'   => 1,
			'template_app'      => 'gddealer',
			'template_location' => 'admin',
			'template_group'    => 'dealers',
			'template_name'     => 'disputeCounts',
			'template_data'     => '$rows, $monthKey',
			'template_content'  => $disputeCountsContent,
		] );
	}
}
catch ( \Exception ) {}

try { unset( \IPS\Data\Store::i()->themes ); }   catch ( \Exception ) {}
try { \IPS\Data\Cache::i()->clearAll(); }        catch ( \Exception ) {}
