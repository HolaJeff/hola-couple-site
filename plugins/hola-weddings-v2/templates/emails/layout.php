<!doctype html>
<html>
    <head>
        <meta charset="utf-8">
        <title><?= esc_html($subject ?? ''); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Basic, email-safe CSS -->
        <style>
            /* Some clients ignore <style>, but most modern ones don’t. Keep it minimal. */
            body { margin:0; padding:0; background:#fafafa; }
            .wrapper { width:100%; background:#fafafa; }
            .container { width:100%; max-width:600px; margin:0 auto; }
            .card {
            background:#ffffff;
            border:1px solid #e5e5e5;
            border-radius:6px;
            overflow:hidden;
            }
            .content { padding:24px 32px; font-family: Arial, sans-serif; color:#333333; line-height:1.5; font-size:15px; }
            .footer {
            font-family: Arial, sans-serif; color:#888888; font-size:12px; line-height:1.4;
            padding:24px 0; text-align:center;
            }
            img.logo { max-width:160px; height:auto; display:block; margin:32px auto 24px auto; }
            @media only screen and (max-width:620px){
            .content { padding:20px 18px !important; }
            }
        </style>
    </head>
    <body>
       <table role="presentation" class="wrapper" cellpadding="0" cellspacing="0">
            <tr>
                <td align="center">

                    <!-- Header -->
                    <table role="presentation" class="container" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center">
                            <img src="https://holaweddings.com/wp-content/uploads/elementor/thumbs/logo_2-pr01nh904c8bwphc3waorr92ykjxmricuutlb46uio.png" alt="Hola Weddings Logo" class="logo">
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td>
                        <table role="presentation" class="card" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                            <td class="content">
                                {{content}}
                            </td>
                            </tr>
                        </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            Hola Weddings<br>
                            Playa del Carmen, Q. Roo, México<br><br>
                            <a href="tel:+1 888 472 7077">+1 888 472 7077</a><br><br>
                            <a href="mailto:hola@holaweddings.com">hola@holaweddings.com</a><br><br>
                            <a href="holaweddings.com">holaweddings.com</a>
                        </td>
                    </tr>
                    </table>

                </td>
            </tr>
        </table>
    </body>
</html>