{{--

Input:

    $canvasSelector
    $chart (Lib/Chart object)

--}}

<?php Lib\HttpAsset::requireAsset('chart.js'); ?>

<script>
    new Chart($('{!! $canvasSelector !!}'), {
        type: '{!! $chart->chartType !!}',
        data: {
            labels : {!! json_encode(array_values($chart->labels)) !!},
            
            datasets: [
                <?php $colorNum = 0; ?>
                @foreach ($chart->datasets as $datasetKey => $dataset)
                    {
                        @if (in_array('showDatasetKeysLegend', $chart->options))
                            label: {!! json_encode($datasetKey) !!},
                        @endif
                        
                        fill: false,
                        lineTension: 0.2,
                        
            			@if ($chart->isColoredByDataValue())
                    		borderColor: {!! json_encode($chart->borderColors) !!},
                    		backgroundColor: {!! json_encode($chart->backgroundColors) !!},
                		@else
                    		borderColor: "{!! $chart->borderColors[$colorNum] !!}",
                    		backgroundColor: "{!! $chart->backgroundColors[$colorNum] !!}",
                		@endif
                		
            			data : {!! json_encode(array_values($dataset)) !!}
                    },
                    <?php $colorNum++; ?>
                @endforeach
            ]
        },
        options: {
            @if ($chart->title != '')
                title: {
                    display: true,
                    text: {!! json_encode($chart->title) !!}
                },
            @endif
            
            @if (in_array('disableTooltips', $chart->options))
                tooltips: { enabled: false },
            @endif
            
            legend: { display: {!! in_array('showDatasetKeysLegend', $chart->options) ? 'true' : 'false' !!} },
            maintainAspectRatio: false,
            responsive: true,
            
            @if (in_array($chart->chartType, [ 'bar', 'line' ]))
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            @endif
        }
    });
</script>
