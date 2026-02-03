let pieOptions = {
    chart: {
        type: 'donut',
        toolbar: {
            show: false
        }
    },
    colors: ['#0077b5', '#005885', '#004d6f', '#003d59'],
    pie: {
        customScale: 0.8,
        size: 200,
        donut: {
            size: '65%'
        }
    },
    series: [],
    labels: ['Anniversary greetings', 'Invitation sent', 'Post liked', 'Profile viewed'],
    legend: {
        position: 'bottom'
    },
    noData: {
        text: 'No activity data available',
        align: 'center',
        verticalAlign: 'middle',
        style: {
            color: '#6c757d',
            fontSize: '14px'
        }
    }
}

const getPieStats = () => {
    $.ajax({
        url: '/piestats',
        method: 'get',
        success: function(res) {
            const pieChartElement = document.querySelector("#pie-chart");
            
            if (!res.stats) {
                // No data - show message
                pieChartElement.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-64 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-3 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                        <p class="text-sm font-medium">No activity data yet</p>
                        <p class="text-xs mt-1">Activity will appear here once you start using the platform</p>
                    </div>
                `;
                return;
            }
            
            pieOptions.series = [
                res.stats['Anniversary greetings'] || 0,
                res.stats['Invitation sent'] || 0,
                res.stats['Post liked'] || 0,
                res.stats['Profile viwed'] || res.stats['Profile viewed'] || 0
            ];
            
            // Check if all values are zero
            const total = pieOptions.series.reduce((a, b) => a + b, 0);
            
            if (total === 0) {
                // All zeros - show message
                pieChartElement.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-64 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-3 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                        <p class="text-sm font-medium">No activity data yet</p>
                        <p class="text-xs mt-1">Activity will appear here once you start using the platform</p>
                    </div>
                `;
                return;
            }
    
            // Clear any existing content
            pieChartElement.innerHTML = '';
            
            var chart = new ApexCharts(pieChartElement, pieOptions);
            chart.render();
        },
        error: function(xhr, status, error) {
            console.error('Error fetching pie stats:', error);
            const pieChartElement = document.querySelector("#pie-chart");
            pieChartElement.innerHTML = `
                <div class="flex flex-col items-center justify-center h-64 text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mb-3 text-gray-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                    </svg>
                    <p class="text-sm font-medium">Unable to load activity data</p>
                    <p class="text-xs mt-1">Please try refreshing the page</p>
                </div>
            `;
        }
    })
}

getPieStats()