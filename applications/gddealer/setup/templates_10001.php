<?php
/**
 * Template seeds for upg_10001.
 *
 * These templates were added after the initial install and must be inserted
 * for sites that upgrade (install.php only runs on fresh installs).
 * Called from setup/upg_10001/upgrade.php step2().
 */

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

$templates10001 = [

	/* ===== dashboardCustomize ===== */
	[
		'template_set_id'   => 1,
		'template_app'      => 'gddealer',
		'template_location' => 'front',
		'template_group'    => 'dealers',
		'template_name'     => 'dashboardCustomize',
		'template_data'     => '$prefs, $saveUrl, $cancelUrl, $csrfKey',
		'template_content'  => <<<'TEMPLATE_EOT'
<div style="max-width:720px">
	<h2 style="margin:0 0 6px;font-size:1.3em;font-weight:800">{lang="gddealer_front_customize_title"}</h2>
	<p style="margin:0 0 20px;color:#666">{lang="gddealer_front_customize_intro"}</p>

	<form method="post" action="{$saveUrl}">
		<input type="hidden" name="csrfKey" value="{$csrfKey}">

		<div class="ipsBox" style="padding:20px;margin-bottom:16px">
			<h3 style="margin:0 0 12px;font-size:1em;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#475569">{lang="gddealer_front_customize_section_visibility"}</h3>
			<div style="display:flex;flex-direction:column;gap:10px">
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_active" value="1" {{if $prefs['show_active']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_active"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_outofstock" value="1" {{if $prefs['show_outofstock']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_outofstock"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_unmatched" value="1" {{if $prefs['show_unmatched']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_unmatched"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_clicks_7d" value="1" {{if $prefs['show_clicks_7d']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_clicks_7d"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_clicks_30d" value="1" {{if $prefs['show_clicks_30d']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_clicks_30d"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_last_import" value="1" {{if $prefs['show_last_import']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_last_import"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="checkbox" name="show_profile_url" value="1" {{if $prefs['show_profile_url']}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_show_profile_url"}</span>
				</label>
			</div>
		</div>

		<div class="ipsBox" style="padding:20px;margin-bottom:20px">
			<h3 style="margin:0 0 12px;font-size:1em;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#475569">{lang="gddealer_front_customize_section_theme"}</h3>
			<div style="display:flex;flex-direction:column;gap:10px">
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="radio" name="card_theme" value="default" {{if $prefs['card_theme'] === 'default'}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_theme_default"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="radio" name="card_theme" value="dark" {{if $prefs['card_theme'] === 'dark'}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_theme_dark"}</span>
				</label>
				<label style="display:flex;align-items:center;gap:10px;cursor:pointer">
					<input type="radio" name="card_theme" value="accent" {{if $prefs['card_theme'] === 'accent'}}checked{{endif}}>
					<span>{lang="gddealer_front_customize_theme_accent"}</span>
				</label>
			</div>
		</div>

		<div style="display:flex;gap:8px">
			<button type="submit" class="ipsButton ipsButton--primary">{lang="gddealer_front_customize_save"}</button>
			<a href="{$cancelUrl}" class="ipsButton ipsButton--normal">Cancel</a>
		</div>
	</form>
</div>
TEMPLATE_EOT,
	],

	/* ===== dealerRegister ===== */
	[
		'template_set_id'   => 1,
		'template_app'      => 'gddealer',
		'template_location' => 'front',
		'template_group'    => 'dealers',
		'template_name'     => 'dealerRegister',
		'template_data'     => '$form, $tier, $name, $guidelinesUrl',
		'template_content'  => <<<'TEMPLATE_EOT'
<div style="max-width:600px;margin:0 auto;padding:24px 16px">
	<div class="ipsBox">
		<div style="padding:32px 28px;text-align:center;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
			<i class="fa-solid fa-store" style="font-size:2.5em;color:#2563eb;margin-bottom:12px;display:block" aria-hidden="true"></i>
			<h1 style="margin:0 0 8px;font-size:1.4em;font-weight:800">Complete Your Dealer Setup</h1>
			<p style="margin:0;color:#666">Your subscription is active. Just a few details to get your dealer profile live.</p>
			<div style="margin-top:12px">
				<span style="background:#2563eb;color:#fff;padding:3px 12px;border-radius:20px;font-size:0.8em;font-weight:700;text-transform:uppercase">{$tier} Plan</span>
			</div>
		</div>
		<div style="padding:28px">
			{$form|raw}
		</div>
	</div>
	<p style="text-align:center;margin-top:16px;font-size:0.85em;color:#888">
		Need help? Visit our <a href="{$guidelinesUrl}" style="color:#2563eb">Review &amp; Setup Guidelines</a>
	</p>
</div>
TEMPLATE_EOT,
	],

	/* ===== dealerDirectory ===== */
	[
		'template_set_id'   => 1,
		'template_app'      => 'gddealer',
		'template_location' => 'front',
		'template_group'    => 'dealers',
		'template_name'     => 'dealerDirectory',
		'template_data'     => '$dealers, $total, $page, $perPage, $pagination, $tier, $sort, $search, $loggedIn, $joinUrl, $directoryUrl',
		'template_content'  => <<<'TEMPLATE_EOT'
<div style="max-width:1400px;margin:0 auto;padding:0 24px;box-sizing:border-box">

	<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--i-border-color,#e0e0e0)">
		<div>
			<h1 style="margin:0 0 4px;font-size:1.6em;font-weight:800">{lang="gddealer_directory_title"}</h1>
			<p style="margin:0;color:#666;font-size:0.9em">{$total} active dealers on GunRack.deals</p>
		</div>
		<a href="{$joinUrl}" class="ipsButton ipsButton--primary">
			<i class="fa-solid fa-store" aria-hidden="true"></i>
			<span>Become a Dealer</span>
		</a>
	</div>

	<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:8px;padding:16px;margin-bottom:24px">
		<form method="get" action="{$directoryUrl}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
			<div style="flex:1 1 200px">
				<label style="display:block;font-size:0.8em;font-weight:600;color:#666;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em">Search</label>
				<input type="text" name="search" value="{$search}" placeholder="Search dealers..." class="ipsInput ipsInput--text" style="width:100%;box-sizing:border-box">
			</div>
			<div style="flex:0 1 160px">
				<label style="display:block;font-size:0.8em;font-weight:600;color:#666;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em">Tier</label>
				<select name="tier" class="ipsInput ipsInput--select" style="width:100%">
					<option value="">All Tiers</option>
					<option value="founding" {{if $tier === 'founding'}}selected{{endif}}>Founding</option>
					<option value="enterprise" {{if $tier === 'enterprise'}}selected{{endif}}>Enterprise</option>
					<option value="pro" {{if $tier === 'pro'}}selected{{endif}}>Pro</option>
					<option value="basic" {{if $tier === 'basic'}}selected{{endif}}>Basic</option>
				</select>
			</div>
			<div style="flex:0 1 160px">
				<label style="display:block;font-size:0.8em;font-weight:600;color:#666;margin-bottom:4px;text-transform:uppercase;letter-spacing:0.05em">Sort By</label>
				<select name="sort" class="ipsInput ipsInput--select" style="width:100%">
					<option value="rating" {{if $sort === 'rating'}}selected{{endif}}>Highest Rated</option>
					<option value="listings" {{if $sort === 'listings'}}selected{{endif}}>Most Listings</option>
					<option value="newest" {{if $sort === 'newest'}}selected{{endif}}>Newest</option>
					<option value="alpha" {{if $sort === 'alpha'}}selected{{endif}}>A&ndash;Z</option>
				</select>
			</div>
			<div>
				<button type="submit" class="ipsButton ipsButton--primary">
					<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
					<span>Filter</span>
				</button>
				{{if $search || $tier}}
				<a href="{$directoryUrl}" class="ipsButton ipsButton--normal" style="margin-left:8px">Clear</a>
				{{endif}}
			</div>
		</form>
	</div>

	{{if count($dealers) === 0}}
	<div style="text-align:center;padding:64px 24px;color:#9ca3af">
		<i class="fa-solid fa-store-slash" style="font-size:3em;margin-bottom:16px;display:block" aria-hidden="true"></i>
		<h3 style="margin:0 0 8px;color:#374151">No dealers found</h3>
		<p style="margin:0">Try adjusting your filters or search terms.</p>
	</div>
	{{else}}
	<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;margin-bottom:32px">
		{{foreach $dealers as $d}}
		<div style="background:#fff;border:1px solid var(--i-border-color,#e0e0e0);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow 0.2s">

			<div style="padding:20px;display:flex;align-items:center;gap:14px;border-bottom:1px solid var(--i-border-color,#f0f0f0)">
				<a href="{$d['profile_url']}" style="flex-shrink:0">
					<span class="ipsUserPhoto ipsUserPhoto--medium">
						<img src="{$d['avatar']}" alt="" loading="lazy">
					</span>
				</a>
				<div style="flex:1;min-width:0">
					<a href="{$d['profile_url']}" style="text-decoration:none;color:inherit">
						<h3 style="margin:0 0 4px;font-size:1em;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{$d['dealer_name']}</h3>
					</a>
					<span style="background:{$d['tier_color']};color:#fff;padding:2px 8px;border-radius:20px;font-size:0.72em;font-weight:700;text-transform:uppercase;letter-spacing:0.04em">{$d['tier_label']}</span>
				</div>
				<div style="text-align:center;flex-shrink:0">
					<div style="font-size:1.4em;font-weight:800;color:{$d['rating_color']};line-height:1">{$d['avg_overall']}</div>
					<div style="font-size:0.7em;color:#9ca3af">/ 5</div>
				</div>
			</div>

			<div style="display:flex;padding:12px 20px;gap:0;border-bottom:1px solid var(--i-border-color,#f0f0f0)">
				<div style="flex:1;text-align:center">
					<div style="font-size:1.1em;font-weight:700">{$d['listing_count']}</div>
					<div style="font-size:0.72em;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Listings</div>
				</div>
				<div style="flex:1;text-align:center;border-left:1px solid var(--i-border-color,#f0f0f0)">
					<div style="font-size:1.1em;font-weight:700">{$d['total_reviews']}</div>
					<div style="font-size:0.72em;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Reviews</div>
				</div>
				<div style="flex:1;text-align:center;border-left:1px solid var(--i-border-color,#f0f0f0)">
					<div style="font-size:0.85em;font-weight:600">{$d['member_since']}</div>
					<div style="font-size:0.72em;color:#9ca3af;text-transform:uppercase;letter-spacing:0.04em">Member Since</div>
				</div>
			</div>

			<div style="padding:12px 16px;display:flex;gap:8px;margin-top:auto">
				<a href="{$d['profile_url']}" class="ipsButton ipsButton--primary ipsButton--small" style="flex:1;text-align:center;justify-content:center">
					<i class="fa-solid fa-store" aria-hidden="true"></i>
					<span>View Profile</span>
				</a>
				{{if $loggedIn}}
				<a href="{$d['follow_url']}" class="ipsButton ipsButton--small {{if $d['is_following']}}ipsButton--primary{{else}}ipsButton--normal{{endif}}" title="{{if $d['is_following']}}Unfollow{{else}}Follow{{endif}} this dealer">
					<i class="fa-solid {{if $d['is_following']}}fa-bell-slash{{else}}fa-bell{{endif}}" aria-hidden="true"></i>
				</a>
				{{endif}}
			</div>
		</div>
		{{endforeach}}
	</div>

	{$pagination|raw}
	{{endif}}

</div>
TEMPLATE_EOT,
	],

];

foreach ( $templates10001 as $tpl )
{
	try
	{
		$exists = (int) \IPS\Db::i()->select( 'COUNT(*)', 'core_theme_templates', [
			'template_app=? AND template_name=?', $tpl['template_app'], $tpl['template_name']
		] )->first();

		if ( $exists === 0 )
		{
			\IPS\Db::i()->insert( 'core_theme_templates', $tpl );
		}
	}
	catch ( \Exception ) {}
}
