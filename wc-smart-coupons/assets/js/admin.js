jQuery(document).ready(function($) {
    
    // Toggle Template-Specific Settings
    function toggleSettings() {
        var style = $('#wcsc_style_selector').val();
        $('.wcsc-adv-setting').hide(); // Hide all first
        
        if (style === 'glass') {
            $('.wcsc-setting-glass').fadeIn();
        } else if (style === 'tag') {
            $('.wcsc-setting-tag').fadeIn();
        } else if (style === 'slider') {
            $('.wcsc-setting-slider').fadeIn();
        }
    }
    
    $('#wcsc_style_selector').on('change', toggleSettings);
    toggleSettings(); // Init

    $('.wcsc-color-field').wpColorPicker();

    // Modern Charts
    if ( document.getElementById('wcscMainChart') && typeof wcscChartData !== 'undefined' ) {
        new Chart(document.getElementById('wcscMainChart'), {
            type: 'line',
            data: {
                labels: wcscChartData.labels,
                datasets: [
                    { label: 'Views', data: wcscChartData.views, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', fill: true, tension: 0.4 },
                    { label: 'Copies', data: wcscChartData.copies, borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.4 }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } }, x: { grid: { display: false } } }
            }
        });

        // Pie Chart
        var totalViews = wcscChartData.views.reduce((a, b) => a + b, 0);
        var totalCopies = wcscChartData.copies.reduce((a, b) => a + b, 0);
        var noAction = Math.max(0, totalViews - totalCopies);

        new Chart(document.getElementById('wcscPieChart'), {
            type: 'doughnut',
            data: {
                labels: ['Copied', 'Just Viewed'],
                datasets: [{
                    data: [totalCopies, noAction],
                    backgroundColor: ['#10b981', '#e5e7eb'],
                    borderWidth: 0
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%' }
        });
    }
});