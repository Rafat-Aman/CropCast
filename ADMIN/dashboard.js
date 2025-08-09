// simple demo charts via Chart.js (data is placeholder)
document.addEventListener('DOMContentLoaded', () => {
  const areaCtx = document.getElementById('areaChart');
  const pieCtx  = document.getElementById('pieChart');

  if (areaCtx) {
    new Chart(areaCtx, {
      type: 'line',
      data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul'],
        datasets: [
          {label:'Products', data:[40,60,45,70,52,68,95], tension:.35, fill:true, borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,.18)'},
          {label:'Services', data:[20,35,25,40,30,36,50], tension:.35, fill:true, borderColor:'#3b82f6', backgroundColor:'rgba(59,130,246,.18)'}
        ]
      },
      options: {
        plugins: { legend: { display:false } },
        scales: { y: { grid: { color:'#f1f5f9'}}, x: { grid: { display:false }}}
      }
    });
  }

  if (pieCtx) {
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: ['Products','Services','Other'],
        datasets: [{ data:[55,35,10], backgroundColor:['#22c55e','#3b82f6','#eab308'] }]
      },
      options: { plugins: { legend: { position:'bottom' } }, cutout: '65%' }
    });
  }
});
