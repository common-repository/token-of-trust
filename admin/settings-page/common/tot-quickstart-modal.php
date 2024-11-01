<?php

function generate_tot_modal($options) {
    // required fields
    if (! ($options['modal_id'] && $options['header'] && $options['body'] && $options['primaryButton']) ) {
        return;
    }
    $modal_id = $options['modal_id'];
    $header = $options['header'];
    $body = $options['body'];
    $primaryButton = $options['primaryButton'];
    $primaryPrice = $options['primaryPrice'] ?? null;
    $primaryTrackableAction = $options['primaryTrackableAction'] ?? null;
    $secondaryButton = $options['secondaryButton'] ?? null;
    $secondaryPrice = $options['secondaryPrice'] ?? null;
    $primaryEmailBody = $options['primaryEmailBody'] ?? null;
    $secondaryEmailBody = $options['secondaryEmailBody'] ?? null;
    $secondaryTrackableAction = $options['secondaryTrackableAction'] ?? null;
    ?>
    <div id="<?php echo $modal_id; ?>" class="tot-modaloverlay">
        <div class="tot-modalcontent">
            <div class="tot-modalheader">
                <span class="tot-close-btn">&times;</span>
                <h2 class="tot-icon"><?php echo $header; ?></h2>
            </div>
            <div class="tot-modalbody">
                <p><?php echo $body; ?></p>
            </div>
            <div class="tot-modalfooter">
                <?php if ($secondaryButton): ?>
                    <div class="tot-btn-price-wrapper">
                        <button class="tot-cta-button secondary <?php if ($secondaryTrackableAction): ?>trackable<?php endif; ?>"
                                <?php if ($secondaryTrackableAction): ?>
                                    data-action="<?php echo $secondaryTrackableAction; ?>"
                                <?php endif; ?>
                            <?php if ($secondaryEmailBody): ?>data-email-body="<?php echo $secondaryEmailBody; ?>"<?php endif; ?>
                        ><?php echo $secondaryButton; ?></button>
                        <?php if ($secondaryPrice): ?>
                            <div class="tot-price-under-button"><?php echo $secondaryPrice; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <div class="tot-btn-price-wrapper">
                    <button class="tot-cta-button primary <?php if ($primaryTrackableAction): ?>trackable<?php endif; ?>"
                        <?php if ($primaryTrackableAction): ?>
                            data-action="<?php echo $primaryTrackableAction; ?>"
                        <?php endif; ?>
                            <?php if ($primaryEmailBody): ?>data-email-body="<?php echo $primaryEmailBody; ?>"<?php endif; ?>
                    ><?php echo $primaryButton; ?></button>
                    <?php if ($primaryPrice): ?>
                        <div class="tot-price-under-button"><?php echo $primaryPrice; ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

?>
