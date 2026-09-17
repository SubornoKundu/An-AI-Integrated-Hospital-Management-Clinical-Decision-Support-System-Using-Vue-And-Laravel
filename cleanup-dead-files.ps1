# Run this from the project root (C:\Users\suborno\Herd\hospital)
# Removes: 43 dead legacy Blade views (pre-Inertia UI, never rendered by
# any controller) + stale Wayfinder-generated files left over from the
# deleted Settings routes/controllers. Safe to run any time - nothing
# here is referenced by any live route, page, or test.

$deadBladeViews = @(
    "resources\views\admin\admins.blade.php",
    "resources\views\admin\ai_insights.blade.php",
    "resources\views\admin\appointments.blade.php",
    "resources\views\admin\beds\index.blade.php",
    "resources\views\admin\categories.blade.php",
    "resources\views\admin\dashboard.blade.php",
    "resources\views\admin\doctors\form.blade.php",
    "resources\views\admin\doctors\index.blade.php",
    "resources\views\admin\doctors\schedule.blade.php",
    "resources\views\admin\feedback.blade.php",
    "resources\views\admin\holidays.blade.php",
    "resources\views\admin\partials\appointments.blade.php",
    "resources\views\admin\partials\categories.blade.php",
    "resources\views\admin\partials\feedback.blade.php",
    "resources\views\admin\partials\holidays.blade.php",
    "resources\views\admin\partials\patients.blade.php",
    "resources\views\admin\partials\revenue.blade.php",
    "resources\views\admin\partials\settings.blade.php",
    "resources\views\admin\patient_detail.blade.php",
    "resources\views\admin\patients.blade.php",
    "resources\views\admin\revenue.blade.php",
    "resources\views\admin\settings.blade.php",
    "resources\views\doctor\appointments.blade.php",
    "resources\views\doctor\calendar.blade.php",
    "resources\views\doctor\chat.blade.php",
    "resources\views\doctor\dashboard.blade.php",
    "resources\views\doctor\earnings.blade.php",
    "resources\views\doctor\notifications.blade.php",
    "resources\views\doctor\partials\calendar.blade.php",
    "resources\views\doctor\partials\chat.blade.php",
    "resources\views\doctor\partials\earnings.blade.php",
    "resources\views\doctor\partials\notifications.blade.php",
    "resources\views\doctor\partials\referrals.blade.php",
    "resources\views\doctor\partials\templates.blade.php",
    "resources\views\doctor\prescription.blade.php",
    "resources\views\doctor\profile.blade.php",
    "resources\views\doctor\referrals.blade.php",
    "resources\views\doctor\templates.blade.php",
    "resources\views\layouts\app.blade.php",
    "resources\views\partials\flash.blade.php",
    "resources\views\patient\chat.blade.php",
    "resources\views\patient\invoice_pdf.blade.php",
    "resources\views\patient\prescription_pdf.blade.php"
)
$deadBladeViews | ForEach-Object { Remove-Item $_ -Force -ErrorAction SilentlyContinue }

# Stale Wayfinder output for the already-deleted Settings controllers/routes.
# These regenerate (correctly, minus these) on your next `npm run build`
# anyway, but removing them now avoids confusion if anyone looks sooner.
$staleWayfinder = @(
    "resources\js\actions\App\Http\Controllers\Settings",
    "resources\js\routes\security",
    "resources\js\routes\appearance",
    "resources\js\routes\user-password",
    "resources\js\routes\profile"
)
$staleWayfinder | ForEach-Object { Remove-Item $_ -Recurse -Force -ErrorAction SilentlyContinue }

# Clean up any now-empty directories left behind
Get-ChildItem resources\views, resources\js\actions, resources\js\routes -Recurse -Directory -ErrorAction SilentlyContinue |
    Sort-Object { $_.FullName.Length } -Descending |
    ForEach-Object {
        if ((Get-ChildItem $_.FullName -Force | Measure-Object).Count -eq 0) {
            Remove-Item $_.FullName -Force
        }
    }

Write-Host "Done. resources/views should now contain only: app.blade.php, emails/password-reset-code.blade.php, patient/invoice_dompdf.blade.php, patient/prescription_dompdf.blade.php"
