<?php
/**
 * Template seeds for upg_10005.
 *
 * Updates the dealerShell front template to render the unread-review badge
 * on the My Reviews tab. The template now references $dealer['new_reviews']
 * which is supplied by dealerSummary() in the dashboard controller. Uses
 * UPDATE if the row exists, INSERT otherwise so it's safe on any install
 * state. Called from setup/upg_10005/upgrade.php step1().
 */

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$dealerShellContent = <<<'TEMPLATE_EOT'
<style>
@media (max-width: 768px) {
  .gdTableWrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
  .gdHelpLayout { flex-direction:column !important; }
  .gdHelpSidebar { width:100% !important; position:static !important; }
  .gdFlexWrap > .ipsProfile__aside { width:100% !important; flex-shrink:1 !important; }
  .gdShellTabs [role="tablist"] { flex-wrap:wrap !important; }
  .gdShellTabs [role="tablist"] a { flex:1 1 auto; text-align:center; padding:10px 12px !important; font-size:0.85em; }
}
@media (max-width: 480px) {
  .gdShellTabs [role="tablist"] a { flex:1 1 100%; }
}
</style>
<div class="gdDealerWrapper" style="width:100%;box-sizing:border-box">

	<header class="ipsPageHeader ipsBox ipsBox--profileHeader ipsPull i-margin-bottom_block" style="width:100%;box-sizing:border-box;border-radius:8px;overflow:hidden;margin-bottom:16px">
		<div class="ipsCoverPhoto ipsCoverPhoto--profile" style="position:relative;overflow:hidden;min-height:160px">
			<div class="ipsCoverPhoto__container" style="width:100%;height:160px;overflow:hidden">
				{{if $dealer['cover_photo_url']}}
					<img src="{$dealer['cover_photo_url']}" class="ipsCoverPhoto__image" alt="" loading="lazy" style="width:100%;height:100%;object-fit:cover">
				{{else}}
					<div class="ipsFallbackImage gdDealerCoverFallback" style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);width:100%;height:160px"></div>
				{{endif}}
			</div>
		</div>
		<div class="ipsCoverPhotoMeta" style="background:#fff;border-top:none;padding:16px 20px;display:flex;gap:16px;align-items:center;flex-wrap:wrap">
			{{if $dealer['avatar_url']}}
			<div class="ipsCoverPhoto__avatar" id="elProfilePhoto" style="margin-top:-50px">
				<span class="ipsUserPhoto ipsUserPhoto--xlarge">
					<img src="{$dealer['avatar_url']}" alt="" loading="lazy" onerror="this.style.display='none'">
				</span>
			</div>
			{{endif}}
			<div class="ipsCoverPhoto__titles" style="flex:1;min-width:200px">
				<div class="ipsCoverPhoto__title">
					<h1 style="margin:0;font-size:1.4em;font-weight:800">{$dealer['dealer_name']}</h1>
				</div>
				<div class="ipsCoverPhoto__desc" style="margin-top:4px">
					<span style="background:{$dealer['tier_color']};color:#fff;padding:2px 10px;border-radius:20px;font-size:0.8em;font-weight:700">{$dealer['tier_label']}</span>
					{{if $dealer['suspended']}}
					<span style="background:#dc2626;color:#fff;padding:2px 10px;border-radius:20px;font-size:0.8em;font-weight:700;margin-left:6px">Suspended</span>
					{{endif}}
				</div>
			</div>
			<div class="ipsCoverPhoto__buttons">
				<a href="{$tabUrls['subscription']}" class="ipsButton ipsButton--inherit ipsButton--small">
					<i class="fa-solid fa-credit-card" aria-hidden="true"></i>
					<span>{lang="gddealer_front_tab_subscription"}</span>
				</a>
			</div>
		</div>
	</header>

	{{if $dealer['onboarding_incomplete']}}
	<div style="background:#fefce8;border:1px solid #fde047;border-radius:8px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
		<div>
			<strong style="color:#854d0e">Complete Your Setup</strong>
			<span style="color:#713f12;margin-left:6px">&mdash; Your account is active but your product feed hasn't been configured yet.</span>
		</div>
		<a href="{$tabUrls['feedSettings']}" class="ipsButton ipsButton--normal ipsButton--small">
			<i class="fa-solid fa-gear" aria-hidden="true"></i>
			<span>Configure Feed Now</span>
		</a>
	</div>
	{{endif}}

	<div class="ipsPwaStickyFix ipsPwaStickyFix--ipsTabs"></div>
	<i-tabs class="ipsTabs ipsTabs--sticky ipsTabs--profile ipsTabs--stretch gdShellTabs gdDealerTabs">
		<div role="tablist" style="display:flex;gap:0;border-bottom:1px solid var(--i-border-color,#e0e0e0);overflow-x:auto;background:#fff;border-radius:8px 8px 0 0">
			<a href="{$tabUrls['overview']}" class="ipsTabs__tab {expression="$activeTab === 'overview' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'overview' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'overview' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'overview' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_overview"}</a>
			<a href="{$tabUrls['feedSettings']}" class="ipsTabs__tab {expression="$activeTab === 'feedSettings' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'feedSettings' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'feedSettings' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'feedSettings' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_feed"}</a>
			<a href="{$tabUrls['listings']}" class="ipsTabs__tab {expression="$activeTab === 'listings' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'listings' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'listings' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'listings' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_listings"}</a>
			<a href="{$tabUrls['unmatched']}" class="ipsTabs__tab {expression="$activeTab === 'unmatched' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'unmatched' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'unmatched' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'unmatched' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_unmatched"}</a>
			<a href="{$tabUrls['analytics']}" class="ipsTabs__tab {expression="$activeTab === 'analytics' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'analytics' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'analytics' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'analytics' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_analytics"}</a>
			<a href="{$tabUrls['reviews']}" class="ipsTabs__tab {expression="$activeTab === 'reviews' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'reviews' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'reviews' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'reviews' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_reviews"}{{if $dealer['new_reviews'] > 0}} <span style="background:#dc2626;color:#fff;border-radius:10px;padding:1px 6px;font-size:0.7em;font-weight:700;margin-left:4px">{$dealer['new_reviews']}</span>{{endif}}</a>
			<a href="{$tabUrls['help']}" class="ipsTabs__tab {expression="$activeTab === 'help' ? 'ipsTabs__activeTab' : ''"}" role="tab" aria-selected="{expression="$activeTab === 'help' ? 'true' : 'false'"}" style="padding:12px 20px;text-decoration:none;font-weight:600;color:{expression="$activeTab === 'help' ? '#2563eb' : '#475569'"};border-bottom:2px solid {expression="$activeTab === 'help' ? '#2563eb' : 'transparent'"};white-space:nowrap">{lang="gddealer_front_tab_help"}</a>
		</div>
	</i-tabs>
	<div id="elDealerTabs_content" class="ipsTabs__panels ipsTabs__panels--profile" style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-top:none;border-radius:0 0 8px 8px;padding:24px">
		<div class="ipsTabs__panel">
			{$body|raw}
		</div>
	</div>

</div>
TEMPLATE_EOT;

try
{
	$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_templates', [
		'template_app=? AND template_name=?', 'gddealer', 'dealerShell'
	] )->first();

	if ( $exists > 0 )
	{
		\IPS\Db::i()->update( 'core_theme_templates',
			[
				'template_data'    => '$dealer, $activeTab, $tabUrls, $body',
				'template_content' => $dealerShellContent,
			],
			[ 'template_app=? AND template_name=?', 'gddealer', 'dealerShell' ]
		);
	}
	else
	{
		\IPS\Db::i()->insert( 'core_theme_templates', [
			'template_set_id'   => 1,
			'template_app'      => 'gddealer',
			'template_location' => 'front',
			'template_group'    => 'dealers',
			'template_name'     => 'dealerShell',
			'template_data'     => '$dealer, $activeTab, $tabUrls, $body',
			'template_content'  => $dealerShellContent,
		] );
	}
}
catch ( \Exception ) {}
