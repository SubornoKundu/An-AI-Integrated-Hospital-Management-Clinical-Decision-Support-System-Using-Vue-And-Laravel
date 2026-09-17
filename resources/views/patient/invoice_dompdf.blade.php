<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 0; }
  .header { background-color: #00d4aa; color: #fff; padding: 18px 22px; }
  .header-table { width: 100%; }
  .hosp-name { font-size: 16px; font-weight: bold; margin-bottom: 3px; }
  .hosp-info { font-size: 10px; opacity: .9; line-height: 1.6; }
  .inv-title { text-align: right; }
  .inv-title h2 { font-size: 22px; font-weight: bold; margin: 0; }
  .inv-title p { font-size: 10px; margin-top: 3px; }

  .body { padding: 18px 22px; }

  .inv-no-row { background-color: #f0fdf9; border: 1px solid #b2f5ea; border-radius: 6px; padding: 8px 12px; margin-bottom: 14px; }
  .inv-no { font-size: 13px; font-weight: bold; color: #00a88a; }
  .inv-date { font-size: 10px; color: #5a6a85; text-align: right; }

  .info-section { margin-bottom: 14px; }
  .info-table { width: 100%; border-collapse: collapse; }
  .info-box { width: 49%; vertical-align: top; background: #f8fafc; border: 1px solid #e4eaf4; padding: 10px 12px; border-radius: 6px; }
  .info-box-spacer { width: 2%; }
  .info-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; font-weight: bold; color: #00d4aa; margin-bottom: 6px; }
  .info-row { font-size: 11px; margin-bottom: 3px; }
  .info-row .lbl { color: #8a9ab8; width: 75px; display: inline-block; }
  .info-row .val { font-weight: 600; }

  .services-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  .services-table th { background: #f4f7fc; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.6px; font-weight: bold; color: #5a6a85; padding: 7px 10px; border-bottom: 2px solid #e4eaf4; }
  .services-table td { padding: 9px 10px; border-bottom: 1px solid #f0f4f8; font-size: 11px; }
  .services-table .subtotal-row td { background: #f4f7fc; font-weight: bold; }
  .services-table .total-row td { background: #f0fdf9; font-weight: bold; color: #00a88a; font-size: 13px; border-top: 2px solid #00d4aa; }

  .diagnosis-box { background: #f0fdf9; border: 1px solid #b2f5ea; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; }
  .diagnosis-box .title { font-weight: bold; color: #00a88a; font-size: 11px; margin-bottom: 4px; }

  .footer { background: #f8fafc; border-top: 1px solid #e4eaf4; padding: 10px 22px; }
  .footer-table { width: 100%; }
  .footer-note { font-size: 9px; color: #b0bdd0; }
  .footer-thank { font-size: 11px; font-weight: bold; text-align: right; }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
  <table class="header-table">
    <tr>
      <td>
        <div class="hosp-name">{{ $hName }}</div>
        <div class="hosp-info">
          {{ $hAddress }}<br>
          @if($hPhone) Tel: {{ $hPhone }} @endif
          @if($hEmail) &nbsp;|&nbsp; {{ $hEmail }} @endif
        </div>
      </td>
      <td class="inv-title">
        <h2>INVOICE</h2>
        <p>Generated: {{ now()->format('d M Y') }}</p>
      </td>
    </tr>
  </table>
</div>

<div class="body">

  {{-- Invoice No --}}
  <table width="100%" style="margin-bottom:14px">
    <tr>
      <td class="inv-no">Invoice #{{ str_pad($appointment->id, 6, '0', STR_PAD_LEFT) }}</td>
      <td class="inv-date">Appointment Date: {{ $appointment->appointment_date->format('d M Y') }}</td>
    </tr>
  </table>

  {{-- Patient & Doctor Info --}}
  <table class="info-table" style="margin-bottom:14px">
    <tr>
      <td class="info-box">
        <div class="info-label">Patient Details</div>
        <div class="info-row"><span class="lbl">Name</span><span class="val">{{ $patient->full_name }}</span></div>
        <div class="info-row"><span class="lbl">Gender</span><span class="val">{{ $patient->gender ?? '—' }}</span></div>
        <div class="info-row"><span class="lbl">Age</span><span class="val">{{ $patient->age ? $patient->age.' yrs' : '—' }}</span></div>
        <div class="info-row"><span class="lbl">Phone</span><span class="val">{{ $patient->contact ?? '—' }}</span></div>
        <div class="info-row"><span class="lbl">Blood</span><span class="val">{{ $patient->blood_group ?? '—' }}</span></div>
      </td>
      <td class="info-box-spacer"></td>
      <td class="info-box">
        <div class="info-label">Doctor Details</div>
        <div class="info-row"><span class="lbl">Doctor</span><span class="val">Dr. {{ $appointment->doctor->name }}</span></div>
        <div class="info-row"><span class="lbl">Specialty</span><span class="val">{{ $appointment->doctor?->category?->name ?? '—' }}</span></div>
        <div class="info-row"><span class="lbl">Date</span><span class="val">{{ $appointment->appointment_date->format('d M Y') }}</span></div>
        <div class="info-row"><span class="lbl">Time</span><span class="val">{{ $appointment->slot_start }} – {{ $appointment->slot_end }}</span></div>
        <div class="info-row"><span class="lbl">Status</span><span class="val">{{ ucfirst($appointment->status) }}</span></div>
      </td>
    </tr>
  </table>

  {{-- Services --}}
  <table class="services-table">
    <thead>
      <tr><th>#</th><th>Description</th><th>Date</th><th style="text-align:right">Amount</th></tr>
    </thead>
    <tbody>
      <tr>
        <td>1</td>
        <td>
          <strong>Medical Consultation</strong><br>
          <span style="color:#8a9ab8;font-size:10px">Dr. {{ $appointment->doctor->name }} — {{ $appointment->doctor?->category?->name ?? 'General Medicine' }}</span>
          @if($appointment->prescription)
          <br><span style="color:#00a88a;font-size:10px">Prescription included</span>
          @endif
        </td>
        <td>{{ $appointment->appointment_date->format('d M Y') }}</td>
        <td style="text-align:right;font-weight:bold">Tk {{ number_format($appointment->consultancy_fee ?? $appointment->doctor->consultancy_fee ?? 0) }}</td>
      </tr>
      <tr class="subtotal-row">
        <td colspan="3">Subtotal</td>
        <td style="text-align:right">Tk {{ number_format($appointment->consultancy_fee ?? $appointment->doctor->consultancy_fee ?? 0) }}</td>
      </tr>
      <tr class="subtotal-row">
        <td colspan="3">Tax / VAT</td>
        <td style="text-align:right">Tk 0</td>
      </tr>
      <tr class="total-row">
        <td colspan="3"><strong>Total Amount</strong></td>
        <td style="text-align:right"><strong>Tk {{ number_format($appointment->consultancy_fee ?? $appointment->doctor->consultancy_fee ?? 0) }}</strong></td>
      </tr>
    </tbody>
  </table>

  {{-- Diagnosis note --}}
  @if($appointment->prescription)
  <div class="diagnosis-box">
    <div class="title">Diagnosis Note</div>
    <div>{{ $appointment->prescription->diagnosis }}</div>
  </div>
  @endif

</div>

<div class="footer">
  <table class="footer-table">
    <tr>
      <td class="footer-note">This is a computer-generated invoice. No signature required.</td>
      <td class="footer-thank">Thank you for choosing {{ $hName }}</td>
    </tr>
  </table>
</div>

</body>
</html>
