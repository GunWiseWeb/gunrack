<?php
if ( !defined( '\\IPS\\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.155 PART 4 of 6 - Step 4 inline CSS.
 *
 * Appends the <style> block to $step4Tpl. Includes:
 *   - shared wizard chrome (progress bar, cards) - same as steps 1-3
 *   - step4Stats grid (4 columns: valid/error/warn/total)
 *   - results table styling
 *   - status badges (green ok / red error / yellow warn)
 *   - <details>/<summary> styling for expandable error lists
 */

if ( !isset( $step4Tpl ) )
{
    throw new \RuntimeException( 'templates_10155_part4.php loaded before part3' );
}

$step4Tpl .= <<<'TEMPLATE_EOT'
<style>
.gdSetupWizard {
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
	color: #0f172a;
	max-width: 1280px;
	margin: 0 auto;
	padding: 24px 16px 80px;
}
.gdSetupWizard *, .gdSetupWizard *::before, .gdSetupWizard *::after { box-sizing: border-box; }

.gdSetupWizard__header { margin-bottom: 24px; }
.gdSetupWizard__header h2 { margin: 0 0 8px; font-size: 1.65em; font-weight: 700; }
.gdSetupWizard__header p { margin: 0; color: #64748b; max-width: 700px; }

.gdSetupWizard__progress { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 24px; }
.gdSetupWizard__steps { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin: 0; padding: 0; list-style: none; }
.gdSetupWizard__step { display: flex; flex-direction: column; gap: 4px; padding: 10px 12px; border-radius: 8px; background: #fff; border: 1px solid #e2e8f0; }
.gdSetupWizard__step.is-current { background: #eff6ff; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
.gdSetupWizard__step.is-done { background: #f0fdf4; border-color: #86efac; }
.gdSetupWizard__step.is-upcoming { opacity: 0.65; }
.gdSetupWizard__stepNum { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #cbd5e1; color: #fff; font-size: 11px; font-weight: 700; }
.gdSetupWizard__step.is-current .gdSetupWizard__stepNum { background: #2563eb; }
.gdSetupWizard__step.is-done .gdSetupWizard__stepNum { background: #16a34a; }
.gdSetupWizard__stepLabel { font-size: 13px; font-weight: 600; }
.gdSetupWizard__stepDesc { font-size: 11.5px; color: #64748b; }

.gdSetupWizard__flash { border-radius: 8px; padding: 14px 16px; margin-bottom: 16px; font-size: 14px; }
.gdSetupWizard__flash strong { display: block; margin-bottom: 4px; }
.gdSetupWizard__flash--success { background: #f0fdf4; border: 1px solid #86efac; color: #14532d; }

.gdSetupWizard__card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
.gdSetupWizard__card h3 { margin: 0 0 8px; font-size: 1.1em; font-weight: 700; }
.gdSetupWizard__card h4 { margin: 0 0 12px; font-size: 1em; font-weight: 600; color: #1e40af; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
.gdSetupWizard__card p { margin: 0 0 12px; color: #475569; font-size: 14px; }
.gdSetupWizard__card p:last-child { margin-bottom: 0; }
.gdSetupWizard__card--warning { background: #fffbeb; border-color: #fcd34d; }

.gdSetupWizard__step4Stats {
	display: grid;
	grid-template-columns: repeat(4, 1fr);
	gap: 12px;
}
.gdSetupWizard__stat { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; display: flex; flex-direction: column; gap: 2px; }
.gdSetupWizard__stat--good { background: #f0fdf4; border-color: #86efac; }
.gdSetupWizard__stat--good .gdSetupWizard__statNum { color: #14532d; }
.gdSetupWizard__stat--bad { background: #fef2f2; border-color: #fca5a5; }
.gdSetupWizard__stat--bad .gdSetupWizard__statNum { color: #991b1b; }
.gdSetupWizard__stat--warn { background: #fffbeb; border-color: #fcd34d; }
.gdSetupWizard__stat--warn .gdSetupWizard__statNum { color: #92400e; }
.gdSetupWizard__stat--neutral { opacity: 0.7; }
.gdSetupWizard__statNum { font-size: 22px; font-weight: 700; color: #0f172a; }
.gdSetupWizard__statLabel { font-size: 12px; color: #64748b; }

.gdSetupWizard__resultsTable { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.gdSetupWizard__resultsTable th { text-align: left; padding: 10px; background: #f8fafc; font-weight: 600; color: #334155; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; }
.gdSetupWizard__resultsTable td { padding: 12px 10px; border-top: 1px solid #f1f5f9; vertical-align: top; }
.gdSetupWizard__colNum { width: 40px; color: #94a3b8; font-weight: 600; text-align: center; }
.gdSetupWizard__colStatus { width: 110px; }
.gdSetupWizard__colUpc { width: 180px; }
.gdSetupWizard__colDetails { width: auto; }

.gdSetupWizard__resultsTable code {
	font-family: 'JetBrains Mono', ui-monospace, monospace;
	font-size: 12px;
	background: #f1f5f9;
	color: #1e40af;
	padding: 2px 7px;
	border-radius: 3px;
}

.gdSetupWizard__statusBadge {
	display: inline-block;
	padding: 3px 10px;
	border-radius: 12px;
	font-size: 11.5px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.4px;
}
.gdSetupWizard__status--ok { background: #dcfce7; color: #14532d; }
.gdSetupWizard__status--warn { background: #fef3c7; color: #92400e; }
.gdSetupWizard__status--error { background: #fee2e2; color: #991b1b; }

.gdSetupWizard__details { margin-bottom: 4px; font-size: 13px; }
.gdSetupWizard__details summary {
	cursor: pointer;
	color: #475569;
	font-weight: 600;
	padding: 2px 0;
}
.gdSetupWizard__details summary:hover { color: #0f172a; }
.gdSetupWizard__details[open] summary { color: #0f172a; margin-bottom: 6px; }
.gdSetupWizard__issuesList { margin: 4px 0 8px; padding-left: 20px; font-size: 12.5px; color: #475569; }
.gdSetupWizard__issuesList li { margin: 3px 0; line-height: 1.5; }
.gdSetupWizard__issueField { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: 11.5px; background: #f1f5f9; padding: 1px 6px; border-radius: 3px; color: #b45309 !important; margin-right: 4px; }
.gdSetupWizard__noIssues { color: #16a34a; font-size: 12.5px; font-style: italic; }

.gdSetupWizard__form { margin-top: 8px; }
.gdSetupWizard__actions { display: flex; justify-content: space-between; gap: 8px; flex-wrap: wrap; }
.gdSetupWizard__btn {
	padding: 10px 18px;
	border-radius: 8px;
	font-weight: 600;
	font-size: 14px;
	border: 1px solid transparent;
	text-decoration: none;
	cursor: pointer;
	transition: all 0.15s;
}
.gdSetupWizard__btn--primary { background: #2563eb; color: #fff; border-color: #2563eb; }
.gdSetupWizard__btn--primary:hover { background: #1d4ed8; border-color: #1d4ed8; }
.gdSetupWizard__btn--ghost { background: #fff; color: #475569; border-color: #cbd5e1; }
.gdSetupWizard__btn--ghost:hover { background: #f8fafc; color: #0f172a; }
.gdSetupWizard__btn--disabled { background: #e2e8f0; color: #94a3b8; border-color: #e2e8f0; cursor: not-allowed; }

@media (max-width: 1100px) {
	.gdSetupWizard__step4Stats { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 720px) {
	.gdSetupWizard { padding: 16px 12px 60px; }
	.gdSetupWizard__steps { grid-template-columns: 1fr 1fr; }
	.gdSetupWizard__step4Stats { grid-template-columns: 1fr; }
	.gdSetupWizard__resultsTable thead { display: none; }
	.gdSetupWizard__resultsTable tr { display: block; padding: 12px 0; border-top: 1px solid #f1f5f9; }
	.gdSetupWizard__resultsTable td { display: block; padding: 4px 10px; }
	.gdSetupWizard__colNum, .gdSetupWizard__colStatus, .gdSetupWizard__colUpc, .gdSetupWizard__colDetails { width: auto; }
	.gdSetupWizard__actions { flex-direction: column-reverse; align-items: stretch; }
	.gdSetupWizard__actions .gdSetupWizard__btn { width: 100%; text-align: center; }
}
@media (max-width: 480px) { .gdSetupWizard__steps { grid-template-columns: 1fr; } }
</style>
TEMPLATE_EOT;
