jQuery(document).ready(function($) {

    var timerInterval;
    var sliderInterval;

    /**
     * 1. MAIN LOAD FUNCTION
     * Fetches coupons via AJAX for every loader div found on the page.
     */
    function loadCoupons() {
        // Find all placeholders (Standard, Mini-Cart, Checkout, etc.)
        $('.wcsc-coupon-loader').each(function() {
            var $loader = $(this);
            var location = $loader.data('location'); // 'standard' or 'minicart'
            var productId = $loader.data('product-id') || 0; // Context for product page targeting

            $.post(wcsc_vars.ajax_url, {
                action: 'wcsc_get_coupons',
                nonce: wcsc_vars.nonce,
                location: location,
                product_id: productId
            }, function(res) {
                if (res.success) {
                    // Replace loader with actual HTML
                    $loader.replaceWith(res.data.html);
                    
                    // Initialize Dynamic Features
                    initCountdowns();
                    initSlider();
                    
                    // Track Views (Once per session per card to avoid spam)
                    $('.wcsc-card').each(function() {
                       if (!$(this).data('view-tracked')) {
                           trackEvent($(this).data('code'), 'view');
                           $(this).data('view-tracked', true);
                       }
                    });
                } else {
                    // Hide loader if no coupons found
                    $loader.hide();
                }
            });
        });
    }

    /**
     * 2. SLIDER PRO LOGIC (Autoplay & Loop)
     */
    function initSlider() {
        // Clear existing interval to prevent duplicates on AJAX reload
        if (sliderInterval) clearInterval(sliderInterval);

        var track = $('.wcsc-slider-track');
        var dotsContainer = $('.wcsc-slider-dots');
        
        // A. Generate Pagination Dots (If container exists)
        if (track.length && dotsContainer.length) {
            dotsContainer.empty();
            var cards = track.find('.wcsc-card');
            cards.each(function(index) {
                dotsContainer.append('<div class="wcsc-dot" data-index="'+index+'"></div>');
            });
            $('.wcsc-dot').first().addClass('active');
        }

        // B. Autoplay Logic
        if (wcsc_vars.slider.autoplay === 'yes' && track.length) {
            var speed = parseInt(wcsc_vars.slider.speed) || 3000;
            var isVertical = wcsc_vars.slider.direction === 'vertical';
            
            sliderInterval = setInterval(function() {
                var scrollProp = isVertical ? 'scrollTop' : 'scrollLeft';
                var dimProp = isVertical ? 'height' : 'width';
                var scrollDim = isVertical ? 'scrollHeight' : 'scrollWidth';
                
                var max = track[0][scrollDim] - track[dimProp]();
                var current = track[scrollProp]();
                
                // Dynamically calculate move distance based on first card size
                var move = 280; // Fallback
                var firstCard = track.find('.wcsc-card').first();
                if(firstCard.length) move = firstCard.outerWidth(true);

                if (current >= max - 10) {
                    // Loop back to start
                    var anim = {}; anim[scrollProp] = 0;
                    track.animate(anim, 500);
                } else {
                    // Scroll next
                    var anim = {}; anim[scrollProp] = '+=' + move;
                    track.animate(anim, 500);
                }
                
                updateActiveDot(track);
            }, speed);
            
            // Pause on Hover
            track.hover(
                function() { clearInterval(sliderInterval); }, 
                function() { initSlider(); }
            );
        }
    }

    // Helper: Update Active Dot based on Scroll Position
    function updateActiveDot(track) {
        if (!$('.wcsc-slider-dots').length) return;
        
        var isVertical = wcsc_vars.slider.direction === 'vertical';
        var scrollPos = isVertical ? track.scrollTop() : track.scrollLeft();
        
        var firstCard = track.find('.wcsc-card').first();
        if(!firstCard.length) return;
        
        var cardSize = isVertical ? firstCard.outerHeight(true) : firstCard.outerWidth(true);
        var index = Math.round(scrollPos / cardSize);
        
        $('.wcsc-dot').removeClass('active').eq(index).addClass('active');
    }

    /**
     * 3. COUNTDOWN TIMER LOGIC
     */
    function initCountdowns() {
        if (timerInterval) clearInterval(timerInterval);

        function tick() {
            var now = Math.floor(Date.now() / 1000);
            
            $('.wcsc-card').each(function() {
                var expires = $(this).data('expires');
                
                // Skip if no expiry set
                if (!expires || expires == 0) return;

                var diff = expires - now;
                var display = $(this).find('.wcsc-timer-display');

                if (diff <= 0) {
                    // Expired State
                    display.text("Expired");
                    $(this).addClass('wcsc-expired').css('opacity', '0.6');
                    $(this).find('.wcsc-btn').prop('disabled', true).text('Expired');
                } else {
                    // Calculation
                    var h = Math.floor(diff / 3600);
                    var m = Math.floor((diff % 3600) / 60);
                    var s = diff % 60;
                    
                    // Pad with zeros
                    h = h < 10 ? "0" + h : h;
                    m = m < 10 ? "0" + m : m;
                    s = s < 10 ? "0" + s : s;
                    
                    var str = h + ":" + m + ":" + s;
                    
                    // If more than 24 hours, show Days
                    if(h > 24) {
                        var days = Math.floor(h / 24);
                        var remainingHours = h % 24;
                        str = days + "d " + remainingHours + "h";
                    }
                    display.text(str);
                }
            });
        }
        
        tick(); // Run immediately
        timerInterval = setInterval(tick, 1000); // Repeat every second
    }

    /**
     * 4. ANALYTICS TRACKING
     */
    function trackEvent(code, type) {
        $.post(wcsc_vars.ajax_url, {
            action: 'wcsc_track_event',
            nonce: wcsc_vars.nonce,
            coupon_code: code,
            event_type: type
        });
    }

    /**
     * 5. INTERACTION HANDLERS (Event Delegation)
     * Using $(document).on() ensures events work after AJAX refreshes.
     */
    
    // Copy Button Click with Ripple & Confetti
    $(document).on('click', '.wcsc-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var card = btn.closest('.wcsc-card');
        var code = card.find('.wcsc-input').val();
        
        // 1. Add Ripple Animation Class
        btn.addClass('animating');

        // Clipboard API
        navigator.clipboard.writeText(code).then(function() {
            var originalText = btn.text();
            btn.text('COPIED!');
            
            // Visual Delight
            fireConfetti(btn[0]);
            
            // Revert text and remove animation class after 2 seconds
            setTimeout(function() { 
                btn.removeClass('animating');
                btn.text(originalText); 
            }, 2000);
            
            // Track Copy Event
            trackEvent(code, 'copy');
        });
    });

    // Slider Manual Navigation (Next)
    $(document).on('click', '.wcsc-next', function() {
        var track = $(this).siblings('.wcsc-slider-track');
        var isVertical = wcsc_vars.slider.direction === 'vertical';
        var prop = isVertical ? 'scrollTop' : 'scrollLeft';
        
        // Calculate move distance dynamically
        var move = 280;
        var firstCard = track.find('.wcsc-card').first();
        if(firstCard.length) move = firstCard.outerWidth(true);

        var anim = {}; anim[prop] = '+=' + move;
        track.animate(anim, 300);
    });

    // Slider Manual Navigation (Prev)
    $(document).on('click', '.wcsc-prev', function() {
        var track = $(this).siblings('.wcsc-slider-track');
        var isVertical = wcsc_vars.slider.direction === 'vertical';
        var prop = isVertical ? 'scrollTop' : 'scrollLeft';
        
        // Calculate move distance dynamically
        var move = 280;
        var firstCard = track.find('.wcsc-card').first();
        if(firstCard.length) move = firstCard.outerWidth(true);

        var anim = {}; anim[prop] = '-=' + move;
        track.animate(anim, 300);
    });

    // Dot Navigation Click
    $(document).on('click', '.wcsc-dot', function() {
        var index = $(this).data('index');
        var track = $(this).closest('.wcsc-container').find('.wcsc-slider-track');
        var isVertical = wcsc_vars.slider.direction === 'vertical';
        
        var move = 280;
        var firstCard = track.find('.wcsc-card').first();
        if(firstCard.length) move = isVertical ? firstCard.outerHeight(true) : firstCard.outerWidth(true);
        
        var pos = index * move;
        var anim = {}; 
        anim[isVertical ? 'scrollTop' : 'scrollLeft'] = pos;
        
        track.animate(anim, 400);
        
        $('.wcsc-dot').removeClass('active');
        $(this).addClass('active');
    });

    /**
     * 6. CONFETTI ANIMATION
     */
    function fireConfetti(element) {
        var colors = ['#f39c12', '#e74c3c', '#2ecc71', '#3498db', '#9b59b6'];
        
        for (var i = 0; i < 20; i++) {
            var confetto = document.createElement('div');
            confetto.style.width = '6px';
            confetto.style.height = '6px';
            confetto.style.position = 'fixed';
            confetto.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            
            // Get button position
            var rect = element.getBoundingClientRect();
            confetto.style.left = (rect.left + rect.width / 2) + 'px';
            confetto.style.top = (rect.top + rect.height / 2) + 'px';
            confetto.style.zIndex = '99999';
            confetto.style.pointerEvents = 'none';
            
            document.body.appendChild(confetto);

            // Random direction
            var angle = Math.random() * Math.PI * 2;
            var velocity = Math.random() * 100 + 50;
            var x = Math.cos(angle) * velocity;
            var y = Math.sin(angle) * velocity;

            // Animate out and fade
            $(confetto).animate({
                left: '+=' + x + 'px',
                top: '+=' + y + 'px',
                opacity: 0
            }, 800, function() {
                $(this).remove(); // Cleanup
            });
        }
    }

    /**
     * 7. INITIALIZATION & LISTENERS
     */
    
    // Initial Load
    loadCoupons();

    // Listener 1: Mini-Cart Refresh (Woostify/Standard Woo)
    $(document.body).on('wc_fragments_refreshed', function() {
        loadCoupons();
    });

    // Listener 2: Checkout Update (Payment/Address change)
    $(document.body).on('updated_checkout', function() {
        loadCoupons();
    });
    
    // Listener 3: Cart Page Update (Quantity change)
    $(document.body).on('updated_wc_div', function() {
        loadCoupons();
    });

});