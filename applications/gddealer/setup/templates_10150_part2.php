<?php
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) ) { header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' ); exit; }

/**
 * v1.0.150 PART 2 of 3 - Inline CSS for setupWizardStep2.
 *
 * Appends the <style> block to $step2Tpl. Must run after part 1.
 */

if ( !isset( $step2Tpl ) )
{
    throw new \RuntimeException( 'templates_10150_part2.php loaded before part1' );
}

$step2Tpl .= <<<'TEMPLATE_EOT'
<style>
.gdSetupWizard {
	font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
	color: #0f172a;
	max-width: 1100px;
	margin: 0 auto;
	padding: 24px 16px 80px;
}
.gdSetupWizard *, .gdSetupWizard *::before, .gdSetupWizard *::after { box-sizing: border-box; }

.gdSetupWizard__header { margin-bottom: 24px; }
.gdSetupWizard__header h2 { margin: 0 0 8px; font-size: 1.65em; font-weight: 700; }
.gdSetupWizard__header p { margin: 0; color: #64748b; max-width: 700px; }

.gdSetupWizard__progress {
	background: #f8fafc;
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	padding: 16px;
	margin-bottom: 24px;
}
.gdSetupWizard__steps {
	display: grid;
	grid-template-columns: repeat(5, 1fr);
	gap: 12px;
	margin: 0;
	padding: 0;
	list-style: none;
}
.gdSetupWizard__step {
	display: flex;
	flex-direction: column;
	gap: 4px;
	padding: 10px 12px;
	border-radius: 8px;
	background: #fff;
	border: 1px solid #e2e8f0;
}
.gdSetupWizard__step.is-current {
	background: #eff6ff;
	border-color: #2563eb;
	box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.gdSetupWizard__step.is-done { background: #f0fdf4; border-color: #86efac; }
.gdSetupWizard__step.is-upcoming { opacity: 0.65; }
.gdSetupWizard__stepNum {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 22px;
	height: 22px;
	border-radius: 50%;
	background: #cbd5e1;
	color: #fff;
	font-size: 11px;
	font-weight: 700;
}
.gdSetupWizard__step.is-current .gdSetupWizard__stepNum { background: #2563eb; }
.gdSetupWizard__step.is-done .gdSetupWizard__stepNum { background: #16a34a; }
.gdSetupWizard__stepLabel { font-size: 13px; font-weight: 600; }
.gdSetupWizard__stepDesc { font-size: 11.5px; color: #64748b; }

.gdSetupWizard__flash {
	border-radius: 8px;
	padding: 14px 16px;
	margin-bottom: 16px;
	font-size: 14px;
}
.gdSetupWizard__flash strong { display: block; margin-bottom: 4px; }
.gdSetupWizard__flash--success { background: #f0fdf4; border: 1px solid #86efac; color: #14532d; }

.gdSetupWizard__card {
	background: #fff;
	border: 1px solid #e2e8f0;
	border-radius: 12px;
	padding: 20px;
	margin-bottom: 16px;
}
.gdSetupWizard__card h3 { margin: 0 0 8px; font-size: 1.1em; font-weight: 700; }
.gdSetupWizard__card h4 { margin: 16px 0 8px; font-size: 1em; font-weight: 600; }
.gdSetupWizard__card h4:first-child { margin-top: 0; }
.gdSetupWizard__card p { margin: 0 0 12px; color: #475569; font-size: 14px; }
.gdSetupWizard__card p:last-child { margin-bottom: 0; }

.gdSetupWizard__card--success { background: #f0fdf4; border-color: #86efac; }
.gdSetupWizard__card--success h4 { color: #14532d; }
.gdSetupWizard__card--success p { color: #166534; }
.gdSetupWizard__card--error { background: #fef2f2; border-color: #fca5a5; }
.gdSetupWizard__card--error h4 { color: #7f1d1d; }
.gdSetupWizard__card--error p { color: #991b1b; }
.gdSetupWizard__card--warning { background: #fffbeb; border-color: #fcd34d; }

.gdSetupWizard__resultRow { display: flex; gap: 14px; align-items: flex-start; }
.gdSetupWizard__resultIcon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 36px;
	height: 36px;
	border-radius: 50%;
	font-size: 18px;
	font-weight: 700;
	flex-shrink: 0;
}
.gdSetupWizard__card--success .gdSetupWizard__resultIcon { background: #16a34a; color: #fff; }
.gdSetupWizard__card--error .gdSetupWizard__resultIcon { background: #dc2626; color: #fff; }
.gdSetupWizard__resultHint { font-size: 12.5px; font-style: italic; }

.gdSetupWizard__step2Meta { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.gdSetupWizard__pill {
	background: #f1f5f9;
	color: #334155;
	padding: 4px 10px;
	border-radius: 14px;
	font-size: 12.5px;
}
.gdSetupWizard__pill strong { color: #0f172a; }
.gdSetupWizard__pill--mono {
	font-family: 'JetBrains Mono', ui-monospace, monospace;
	font-size: 11.5px;
	max-width: 100%;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.gdSetupWizard__metaTable, .gdSetupWizard__fieldsTable {
	width: 100%;
	border-collapse: collapse;
	font-size: 13.5px;
	margin: 8px 0 12px;
}
.gdSetupWizard__metaTable th, .gdSetupWizard__metaTable td,
.gdSetupWizard__fieldsTable th, .gdSetupWizard__fieldsTable td {
	padding: 8px 12px;
	text-align: left;
	border-bottom: 1px solid #e2e8f0;
}
.gdSetupWizard__metaTable th { background: #f8fafc; font-weight: 600; color: #334155; width: 160px; }
.gdSetupWizard__fieldsTable th { background: #f8fafc; font-weight: 600; color: #334155; }
.gdSetupWizard__metaTable tr:last-child td, .gdSetupWizard__metaTable tr:last-child th,
.gdSetupWizard__fieldsTable tbody tr:last-child td { border-bottom: none; }

.gdSetupWizard__metaTable code, .gdSetupWizard__fieldsTable code {
	font-family: 'JetBrains Mono', ui-monospace, monospace;
	font-size: 12px;
	background: #f1f5f9;
	color: #1e40af;
	padding: 1px 6px;
	border-radius: 3px;
}
.gdSetupWizard__sample {
	color: #475569 !important;
	max-width: 360px;
	display: inline-block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
	vertical-align: middle;
}
.gdSetupWizard__count {
	display: inline-block;
	min-width: 30px;
	background: #1e40af;
	color: #fff;
	padding: 2px 8px;
	border-radius: 11px;
	font-size: 11.5px;
	font-weight: 600;
	text-align: center;
}
.gdSetupWizard__warnInline { color: #b45309; font-size: 12px; }

.gdSetupWizard__preview {
	background: #0f172a;
	color: #e2e8f0;
	padding: 14px 16px;
	border-radius: 8px;
	font-family: 'JetBrains Mono', ui-monospace, monospace;
	font-size: 11.5px;
	line-height: 1.5;
	max-height: 240px;
	overflow: auto;
	white-space: pre-wrap;
	word-break: break-all;
	margin: 0;
}

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

@media (max-width: 720px) {
	.gdSetupWizard { padding: 16px 12px 60px; }
	.gdSetupWizard__steps { grid-template-columns: 1fr 1fr; }
	.gdSetupWizard__metaTable th { width: auto; }
	.gdSetupWizard__sample { max-width: 200px; }
	.gdSetupWizard__actions { flex-direction: column-reverse; align-items: stretch; }
	.gdSetupWizard__actions .gdSetupWizard__btn { width: 100%; text-align: center; }
}
@media (max-width: 480px) {
	.gdSetupWizard__steps { grid-template-columns: 1fr; }
}
</style>
TEMPLATE_EOT;
