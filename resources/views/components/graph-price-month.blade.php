@php use Illuminate\Support\Js; @endphp
@props(['labels' => '', 'data' => 0, 'id' => 'primary', 'label' => ''])

<div>
    <canvas id="{{ $id }}" aria-label="{{ $label }}" role="img">{{ $label }}</canvas>

    @pushOnce('scripts')
        <script src="{{ mix('/vendor/Chart.min.js') }}"></script>
    @endPushOnce

    @push('scripts')
        <script>
          new Chart(document.getElementById('{{ $id }}'), {
            type: 'bar',
            data: {
              labels : {{ Js::from($labels) }},
              datasets : [
                {
                  backgroundColor : "rgba(151,187,205,0.5)",
                  borderColor : "rgba(151,187,205,1)",
                  borderWidth: 1,
                  label: 'Price',
                  data : {{ Js::from($data) }},
                }
              ]
            },
            options: {
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: {
                    // Include a dollar sign in the ticks
                    callback: function(value, index, ticks) {
                      return value !== 0 ? ('$' + value) : value;
                    }
                  }
                }
              },
              legend: {
                display: false
              },
              plugins: {
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      let label = context.dataset.label || '';

                      if (label) {
                        label += ': ';
                      }
                      if (context.parsed.y !== null) {
                        label += new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(context.parsed.y);
                      }
                      return label;
                    }
                  }
                }
              }
            }
          });
        </script>
    @endPush
</div>