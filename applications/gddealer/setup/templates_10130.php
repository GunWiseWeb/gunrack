<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

$canonicalDealerShell = <<<'TEMPLATE_EOT'
<div class="gdDealerApp">

	<div class="gdMobileBar">
		<div class="gdMobileBar__brand">
			<span class="gdSidebar__brandMark">GD</span>
			<div>
				<div class="gdSidebar__brandText">{$dealer['dealer_name']}</div>
				<div class="gdSidebar__brandSub">{$dealer['tier_label']}</div>
			</div>
		</div>
		<a href="#gdDrawer" class="gdMobileBar__menuBtn" aria-label="Open menu">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
		</a>
	</div>

	<div class="gdDrawer" id="gdDrawer">
		<div class="gdDrawer__panel">
			<a href="#" class="gdDrawer__close" aria-label="Close">&times;</a>
			{template="dealerSidebar" group="dealers" app="gddealer" params="$dealer, $activeTab, $nav"}
		</div>
	</div>

	<div class="gdDealerShell">
		<aside class="gdSidebar">
			{template="dealerSidebar" group="dealers" app="gddealer" params="$dealer, $activeTab, $nav"}
		</aside>

		<main class="gdMain">
			{$body|raw}
		</main>
	</div>

</div>
TEMPLATE_EOT;

try
{
	\IPS\Db::i()->update( 'core_theme_templates',
		[
			'template_data'    => '$dealer, $activeTab, $nav, $body',
			'template_content' => $canonicalDealerShell,
			'template_updated' => time(),
		],
		[ 'template_app=? AND template_location=? AND template_group=? AND template_name=?',
		  'gddealer', 'front', 'dealers', 'dealerShell' ]
	);
}
catch ( \Throwable ) {}
