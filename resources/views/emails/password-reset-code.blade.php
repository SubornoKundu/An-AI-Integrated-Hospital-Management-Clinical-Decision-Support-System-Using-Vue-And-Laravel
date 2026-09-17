<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your Password Reset Code</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family:Arial, Helvetica, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding:32px 16px;">
<tr>
<td align="center">
<table role="presentation" width="480" cellpadding="0" cellspacing="0" style="max-width:480px; width:100%; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">

<tr>
<td style="background:linear-gradient(135deg,#2563EB,#0891B2); padding:28px 32px;">
<span style="color:#ffffff; font-size:18px; font-weight:700;">🏥 {{ config('app.name', 'MediCare Hospital') }}</span>
</td>
</tr>

<tr>
<td style="padding:32px;">
<p style="margin:0 0 8px; font-size:15px; color:#1e293b;">Hi,</p>
<p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#475569;">
We received a request to reset the password for your <strong>{{ $roleLabel }}</strong> account.
Use the code below to continue — it expires in <strong>15 minutes</strong>.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
<tr>
<td align="center" style="background-color:#f0f5ff; border:1px solid #dbe6fb; border-radius:12px; padding:20px;">
<span style="font-size:32px; font-weight:700; letter-spacing:8px; color:#2563EB;">{{ $code }}</span>
</td>
</tr>
</table>

<p style="margin:0 0 8px; font-size:13px; line-height:1.6; color:#94a3b8;">
If you didn't request this, you can safely ignore this email — your password won't be changed.
</p>
</td>
</tr>

<tr>
<td style="padding:20px 32px; background-color:#f8fafc; border-top:1px solid #eef2f7;">
<p style="margin:0; font-size:12px; color:#94a3b8;">
This is an automated message from {{ config('app.name', 'MediCare Hospital') }}. Please don't reply to this email.
</p>
</td>
</tr>

</table>
</td>
</tr>
</table>
</body>
</html>
