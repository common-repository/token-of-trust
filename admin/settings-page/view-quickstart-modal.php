<?php
require 'common/tot-quickstart-modal.php';

generate_tot_modal([
    'modal_id' => 'whiteGloveModal',
    'header' => 'White Glove Setup',
    'body' => 'Need help getting set up? Our professional success team is here to help and can have you live within a few hours!',
    'primaryButton' => '2 business days',
    'primaryPrice' => '$199',
    'primaryTrackableAction' => 'clicked_service_whiteglove_standard',
    'secondaryButton' => '4 business hours',
    'secondaryPrice' => '$500',
    'secondaryTrackableAction' => 'clicked_service_whiteglove_premium',
    'primaryEmailBody' => 'I am interested in the standard white glove service setup. Please contact me with regards to scheduling as soon as possible.',
    'secondaryEmailBody' => 'I am interested in the express white glove service setup. Please contact me with regards to scheduling expedited service as soon as possible.',
]);


generate_tot_modal([
    'modal_id' => 'contactSupportModal',
    'header' => 'Contact Support',
    'body' => 'Token of Trust has a dedicated support team that responds within 2 business days. Premium Support is also available with 2 hour response time for those that have urgent needs!',
    'primaryButton' => '2 business days',
    'primaryPrice' => 'Free',
    'primaryTrackableAction' => 'clicked_contact_support_standard',
    'secondaryButton' => '2 business hours',
    'secondaryPrice' => '$500/month',
    'secondaryTrackableAction' => 'clicked_contact_support_premium',
]);

generate_tot_modal([
    'modal_id' => 'premiumSupportModal',
    'header' => 'Premium Support',
    'body' => 'Token of Trust has a dedicated support team that responds to Premium Support requests within 2 business hours.<br><br>Please contact us:<br>by phone: <a href="tel:+1 (833)738-0038">+1(833)738-0038</a><br>',
    'primaryButton' => 'Email',
    'primaryPrice' => '<a href="mailto:onboarding@tokenoftrust.com">onboarding@tokenoftrust.com</a>',
    'primaryEmailBody' => 'I need urgent support with an issue or would like to ask about your premium support package.',
]);

generate_tot_modal([
    'modal_id' => 'standardSupportModal',
    'header' => 'Standard Support',
    'body' => 'Token of Trust has a dedicated support team and is able to respond to standard support requests within 2 business hours. If you need faster support please consider our Premium Support option.<br><br>Please contact us:<br>by phone: <a href="tel:+1 (833)738-0038">+1(833)738-0038</a><br>',
    'primaryButton' => 'Email',
    'primaryPrice' => '<a href="mailto:onboarding@tokenoftrust.com">onboarding@tokenoftrust.com</a>',
    'primaryEmailBody' => 'I have a standard support request: PLEASE PROVIDE DETAILS HERE.',
]);
?>
