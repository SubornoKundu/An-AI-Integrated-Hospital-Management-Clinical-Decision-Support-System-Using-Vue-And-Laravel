<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a2e; margin: 0; padding: 0; }

  .header { background-color: #0984e3; color: #fff; padding: 18px 22px; }
  .header-table { width: 100%; }
  .hosp-name { font-size: 15px; font-weight: bold; margin-bottom: 3px; }
  .hosp-info { font-size: 10px; line-height: 1.6; }
  .rx-symbol { font-size: 36px; font-weight: bold; font-style: italic; color: rgba(255,255,255,0.3); text-align: right; line-height: 1; }

  .doctor-strip { background: #f0fdf9; border-bottom: 2px solid #00d4aa; padding: 10px 22px; }
  .doctor-table { width: 100%; }
  .doctor-name { font-size: 13px; font-weight: bold; }
  .doctor-spec { font-size: 11px; color: #00a88a; font-weight: bold; margin-top: 2px; }
  .rx-date { font-size: 10px; color: #5a6a85; text-align: right; }

  .body { padding: 16px 22px; }

  .patient-table { width: 100%; background: #f8fafc; border: 1px solid #e4eaf4; margin-bottom: 14px; padding: 10px 12px; }
  .pi-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; color: #8a9ab8; display: block; margin-bottom: 2px; }
  .pi-val { font-size: 11px; font-weight: 600; }

  .section { margin-bottom: 14px; }
  .section-title { font-size: 9px; text-transform: uppercase; letter-spacing: 0.8px; font-weight: bold; color: #5a6a85; border-bottom: 1px solid #e4eaf4; padding-bottom: 4px; margin-bottom: 8px; }
  .content-box { background: #f8fafc; border: 1px solid #e4eaf4; border-radius: 5px; padding: 9px 12px; font-size: 11px; line-height: 1.8; white-space: pre-wrap; }
  .diagnosis-box { background: #f0fdf9; border: 1px solid #b2f5ea; border-radius: 5px; padding: 9px 12px; font-size: 12px; font-weight: 600; line-height: 1.7; }
  .followup-box { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 5px; padding: 7px 12px; font-size: 11px; color: #2563eb; font-weight: 600; display: inline-block; }

  .signature-area { margin-top: 30px; text-align: right; }
  .sig-line { border-top: 1.5px solid #1a1a2e; padding-top: 5px; display: inline-block; width: 180px; text-align: center; }
  .sig-name { font-size: 11px; font-weight: bold; }
  .sig-spec { font-size: 10px; color: #8a9ab8; margin-top: 2px; }

  .footer { background: #f8fafc; border-top: 1px solid #e4eaf4; padding: 10px 22px; margin-top: 20px; }
  .footer-table { width: 100%; }
  .footer-note { font-size: 9px; color: #b0bdd0; }
  .footer-disclaimer { font-size: 9px; color: #8a9ab8; font-style: italic; text-align: right; }
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
        </div>
      </td>
      <td class="rx-symbol">Rx</td>
    </tr>
  </table>
</div>

{{-- Doctor Strip --}}
<div class="doctor-strip">
  <table class="doctor-table">
    <tr>
      <td>
        <div class="doctor-name">Dr. {{ $appointment->doctor->name }}</div>
        <div class="doctor-spec">{{ $appointment->doctor?->category?->name ?? 'General Medicine' }}</div>
      </td>
      <td class="rx-date">
        Date: <strong>{{ $appointment->appointment_date->format('d M Y') }}</strong>
      </td>
    </tr>
  </table>
</div>

<div class="body">

  {{-- Patient Info --}}
  <table class="patient-table" style="margin-bottom:14px">
    <tr>
      <td style="width:25%;padding:4px 8px">
        <span class="pi-label">Patient</span>
        <span class="pi-val">{{ $patient->full_name }}</span>
      </td>
      <td style="width:15%;padding:4px 8px">
        <span class="pi-label">Age</span>
        <span class="pi-val">{{ $patient->age ? $patient->age.' yrs' : '—' }}</span>
      </td>
      <td style="width:15%;padding:4px 8px">
        <span class="pi-label">Gender</span>
        <span class="pi-val">{{ $patient->gender ?? '—' }}</span>
      </td>
      <td style="width:15%;padding:4px 8px">
        <span class="pi-label">Blood</span>
        <span class="pi-val">{{ $patient->blood_group ?? '—' }}</span>
      </td>
      <td style="width:20%;padding:4px 8px">
        <span class="pi-label">Appt No.</span>
        <span class="pi-val">#{{ str_pad($appointment->id, 5, '0', STR_PAD_LEFT) }}</span>
      </td>
    </tr>
  </table>

  {{-- Diagnosis --}}
  <div class="section">
    <div class="section-title">Diagnosis</div>
    <div class="diagnosis-box">{{ $appointment->prescription->diagnosis }}</div>
  </div>

  {{-- Medicines --}}
  <div class="section">
    <div class="section-title">Medicines / Prescription</div>
    <div class="content-box">{{ $appointment->prescription->medicines }}</div>
  </div>

  {{-- Instructions --}}
  @if($appointment->prescription->instructions)
  <div class="section">
    <div class="section-title">Instructions & Advice</div>
    <div class="content-box">{{ $appointment->prescription->instructions }}</div>
  </div>
  @endif

  {{-- Follow-up --}}
  @if($appointment->prescription->follow_up_date)
  <div class="section">
    <div class="section-title">Follow-up</div>
    <div class="followup-box">Next Visit: {{ $appointment->prescription->follow_up_date->format('d M Y') }}</div>
  </div>
  @endif

  {{-- Signature --}}
  <div class="signature-area">
    <div class="sig-line">
      <div class="sig-name">Dr. {{ $appointment->doctor->name }}</div>
      <div class="sig-spec">{{ $appointment->doctor?->category?->name ?? 'General Medicine' }}</div>
      <div class="sig-spec">{{ $hName }}</div>
    </div>
  </div>

</div>

<div class="footer">
  <table class="footer-table">
    <tr>
      <td class="footer-note">{{ $hName }} · {{ $hAddress }}</td>
      <td class="footer-disclaimer">This prescription is valid for 30 days from the date of issue.</td>
    </tr>
  </table>
</div>

</body>
</html>
