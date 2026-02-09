<?php 
// Variables: $coupon, $is_eligible, $is_applied, $expiry, $scarcity_text, $pct, $label, $code
$card_class = $is_applied ? 'wcsc-applied' : ( !$is_eligible ? 'wcsc-locked' : '' );
?>
<div class="wcsc-card <?php echo $card_class; ?>" 
     data-code="<?php echo esc_attr( $code ); ?>"
     data-expires="<?php echo esc_attr( $expiry ); ?>">
    
    <div class="wcsc-tear-line"></div>

    <div class="wcsc-header">
        <div class="wcsc-discount-row">
            <span class="wcsc-discount"><?php echo esc_html( $label ); ?></span>
        </div>
        <div class="wcsc-badges">
            <?php if ( $is_applied ) : ?>
                <span class="wcsc-badge applied"><span class="dashicons dashicons-yes"></span></span>
            <?php elseif ( ! $is_eligible ) : ?>
                <span class="wcsc-badge locked"><span class="dashicons dashicons-lock"></span></span>
            <?php elseif ( ! empty( $scarcity_text ) ) : ?>
                <span class="wcsc-badge hot">🔥</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="wcsc-body">
        <p class="wcsc-desc"><?php echo esc_html( $coupon->get_description() ?: 'Limited offer' ); ?></p>
        <?php if ( $expiry > 0 ) : ?>
            <div class="wcsc-timer"><span class="dashicons dashicons-clock"></span> <span class="wcsc-timer-display">--:--</span></div>
        <?php endif; ?>
    </div>
    
    <div class="wcsc-footer">
        <?php if ( $is_applied ) : ?>
             <div class="wcsc-msg-success">APPLIED</div>
        <?php elseif ( ! $is_eligible ) : ?>
            <div class="wcsc-progress-wrap">
                <div class="wcsc-bar"><div class="wcsc-fill" style="width: <?php echo $pct; ?>%;"></div></div>
                <small>Add <?php echo wc_price( $missing ); ?></small>
            </div>
        <?php else : ?>
            <div class="wcsc-copy-row">
                <input type="text" readonly value="<?php echo esc_attr( $code ); ?>" class="wcsc-input">
                <button class="wcsc-btn">COPY</button>
            </div>
        <?php endif; ?>
    </div>
</div>