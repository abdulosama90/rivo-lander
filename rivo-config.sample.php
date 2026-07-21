<?php
/**
 * Rivo secrets. Upload as  rivo-config.php  to the ACCOUNT ROOT:
 *   /home/sites/14a/b/bd63054524/rivo-config.php
 * i.e. one level ABOVE public_html — never inside public_html, never in git.
 */
return [
    'general_api_key'      => '',  // 20i general API key
    'oauth_client_key'     => '',  // 20i OAuth client key
    'stripe_secret_key'    => '',  // sk_live_...
    'stripe_webhook_secret'=> '',  // whsec_...
];
