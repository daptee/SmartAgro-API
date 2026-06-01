<!DOCTYPE html>
<html lang="es" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
  <meta charset="utf-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
  <!--[if mso]>
    <xml><o:officedocumentsettings><o:pixelsperinch>96</o:pixelsperinch></o:officedocumentsettings></xml>
  <![endif]-->
  <title>Planes empresa próximos a vencer (30 días)</title>
  <link href="https://fonts.googleapis.com/css?family=Montserrat:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,500;1,600;1,700" rel="stylesheet" media="screen">
  <style>
    .hover-underline:hover {
      text-decoration: underline !important;
    }

    @media (max-width: 600px) {
      .sm-w-full {
        width: 100% !important;
      }

      .sm-px-24 {
        padding-left: 24px !important;
        padding-right: 24px !important;
      }

      .sm-py-32 {
        padding-top: 32px !important;
        padding-bottom: 32px !important;
      }
    }
  </style>
</head>

<body style="margin: 0; width: 100%; padding: 0; word-break: break-word; -webkit-font-smoothing: antialiased; background-color: #eceff1;">
  <div style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; display: none;">Planes empresa próximos a vencer en 30 días</div>
  <div role="article" aria-roledescription="email" aria-label="Planes empresa próximos a vencer" lang="es" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly;">
    <table style="width: 100%; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td align="center" style="mso-line-height-rule: exactly; background-color: #eceff1; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;">
          <table class="sm-w-full" style="width: 600px;" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <td class="sm-py-32 sm-px-24" style="mso-line-height-rule: exactly; padding: 48px; text-align: center; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif;">
                <a href="{{ config('services.url_front') }}" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly;">
                  <img src="https://smartagro.io/firmas/smartagro-logo.png" width="200" alt="Smartagro" style="max-width: 100%; vertical-align: middle; line-height: 100%; border: 0;">
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" class="sm-px-24" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly;">
                <table style="width: 100%;" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td class="sm-px-24" style="mso-line-height-rule: exactly; border-radius: 4px; background-color: #ffffff; padding: 48px; text-align: left; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; font-size: 16px; line-height: 24px; color: #626262;">

                      <p style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; margin-bottom: 0; font-size: 20px; font-weight: 600; color: #263238;">Notificación de Administrador</p>

                      <p style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; margin: 0; margin-bottom: 24px; margin-top: 16px;">
                        Los siguientes planes de empresa <strong>vencen en exactamente 30 días</strong>. Por favor, contactar a las empresas para gestionar la renovación.
                      </p>

                      <table style="width: 100%; border-collapse: collapse;" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                          <th style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; padding: 10px 12px; background-color: #16A860; color: #ffffff; text-align: left; font-size: 13px; font-weight: 600; border-radius: 4px 0 0 0;">Empresa</th>
                          <th style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; padding: 10px 12px; background-color: #16A860; color: #ffffff; text-align: left; font-size: 13px; font-weight: 600;">Inicio del plan</th>
                          <th style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; padding: 10px 12px; background-color: #16A860; color: #ffffff; text-align: left; font-size: 13px; font-weight: 600; border-radius: 0 4px 0 0;">Vencimiento</th>
                        </tr>
                        @foreach ($plans as $plan)
                        <tr>
                          <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; padding: 9px 12px; border-bottom: 1px solid #eceff1; font-size: 14px; color: #263238;">
                            <strong>{{ $plan->company->company_name ?? '—' }}</strong>
                          </td>
                          <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; padding: 9px 12px; border-bottom: 1px solid #eceff1; font-size: 14px; color: #626262;">
                            {{ $plan->date_start ? $plan->date_start->format('d/m/Y') : '—' }}
                          </td>
                          <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; padding: 9px 12px; border-bottom: 1px solid #eceff1; font-size: 14px; color: #e53e3e; font-weight: 600;">
                            {{ $plan->date_end ? $plan->date_end->format('d/m/Y') : '—' }}
                          </td>
                        </tr>
                        @endforeach
                      </table>

                      <table style="width: 100%;" cellpadding="0" cellspacing="0" role="presentation">
                        <tr>
                          <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; padding-top: 32px; padding-bottom: 32px;">
                            <div style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; height: 1px; background-color: #eceff1; line-height: 1px;">&zwnj;</div>
                          </td>
                        </tr>
                      </table>

                      <p style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; margin: 0; font-size: 14px; color: #9e9e9e;">Sistema automático de SmartAgro</p>

                    </td>
                  </tr>
                  <tr>
                    <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; height: 20px;"></td>
                  </tr>
                  <tr>
                    <td style="mso-line-height-rule: exactly; padding-left: 48px; padding-right: 48px; font-family: Montserrat, -apple-system, 'Segoe UI', sans-serif; font-size: 14px; color: #626262;">
                      <p align="center" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; margin-bottom: 16px; cursor: default;">
                        <a href="https://www.linkedin.com/company/smartagrook" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; color: #263238; text-decoration: none;"><img width="20" height="20" src="https://smartagro.io/firmas/linkedin.png" alt="Linkedin" style="max-width: 100%; vertical-align: middle; line-height: 100%; border: 0; margin-right: 12px;"></a>
                        &bull;
                        <a href="https://www.instagram.com/smartagrook" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; color: #263238; text-decoration: none;"><img width="20" height="20" src="https://smartagro.io/firmas/instagram.png" alt="Instagram" style="max-width: 100%; vertical-align: middle; line-height: 100%; border: 0; margin-left: 12px; margin-right: 12px;"></a>
                        &bull;
                        <a href="https://www.tiktok.com/@smartagrook" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; color: #263238; text-decoration: none;"><img width="20" height="20" src="https://smartagro.io/firmas/tiktok.png" alt="TikTok" style="max-width: 100%; vertical-align: middle; line-height: 100%; border: 0; margin-left: 12px; margin-right: 12px;"></a>
                        &bull;
                        <a href="https://x.com/smartagrook" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; color: #263238; text-decoration: none;"><img width="20" height="20" src="https://smartagro.io/firmas/x.png" alt="X" style="max-width: 100%; vertical-align: middle; line-height: 100%; border: 0; margin-left: 12px;"></a>
                      </p>
                      <p style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; color: #626262;">
                        El uso de nuestro servicio y sitio web está sujeto a nuestros
                        <a href="{{ config('services.url_front') }}/terminos-y-condiciones/" class="hover-underline" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; color: #16A860; text-decoration: none;">Términos de uso</a> y
                        <a href="{{ config('services.url_front') }}/politicas-de-privacidad/" style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; color: #16A860; text-decoration: none;">Política de privacidad</a>.
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <td style="font-family: 'Montserrat', sans-serif; mso-line-height-rule: exactly; height: 16px;"></td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </div>
</body>

</html>
