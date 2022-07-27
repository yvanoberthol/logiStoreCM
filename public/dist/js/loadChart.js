// methode per mettre à jour la vue du tableau statisque en fonction des critères

function loadAreaChart(ctxElem, datas, title, thousandSeparator, areacolors=[]){
    Chart.defaults.defaultFontFamily='-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.defaultFontColor="#292b2c";
    const amountArray = datas.map(object => {return object.amount});

    let percent = 0;

    const colorDefault = areacolors[2];
    let color = colorDefault;
    if (amountArray.length > 0){
        color = amountArray.map(x => colorDefault);
        color[argMax(amountArray)] = areacolors[4];
        color[argMin(amountArray)] = areacolors[5];

        console.log(color);

        const valueMax = amountArray[argMax(amountArray)];
        percent = percentageLimitChart(valueMax);
    }

    let ctx = document.getElementById(ctxElem).getContext('2d');

    return new Chart(ctx,{
        type:"line",
        data:{
            labels: datas.map(object => {return object.date}),
            datasets:[
                {
                    fill:true,
                    stack:true,
                    label: title,
                    lineTension:.5,
                    backgroundColor:transparentize(areacolors[0]),
                    borderColor:areacolors[1],
                    pointRadius:4,
                    pointBackgroundColor: color,
                    pointBorderColor:areacolors[3],
                    pointHoverRadius:5,
                    pointHoverBackgroundColor:"rgba(2,117,216,1)",
                    pointHitRadius:20,
                    pointBorderWidth:2,
                    data: amountArray
                }
            ]
        },
        options: {
            scales:
                {
                    x:{
                        time:{unit:"date"},
                        gridLines:{display:!1},
                        fontSize: 12,
                        ticks:{z:31}
                    },
                    y:{
                        min:0,max:percent,
                        ticks:{z:5},
                        callback: function(value){
                            return formatNumber(value,thousandSeparator)
                        }
                        ,
                        gridLines:{display:!0}
                       /* ,
                        gridLines:{color:"rgba(0, 0, 0, .125)"}*/
                    }
                },
            tooltips: {
                callbacks: {
                    label: function (tooltipItem, data) {
                        let label = data.datasets[tooltipItem.datasetIndex].label || '';

                        if (label){
                            label += ': ';
                        }
                        label += formatNumber(tooltipItem.yLabel,thousandSeparator);
                        return label;
                    }
                }
            },
            plugins:{
                legend: {
                    display: (areacolors[6]==='true')
                }
            }
        }
    });
}

function loadMultipleAreaChart(ctxElem, datas,titles,thousandSeparator,areacolors=[]){
    Chart.defaults.defaultFontFamily='-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.defaultFontColor="#292b2c";

    let amountArray = [];
    let percent = [];
    let color = [];
    let valueMax = [];
    let data = [];
    let labels = [];
    for(let i = 0;i < datas.length;i++){
        amountArray[i] = datas[i].map(object => {return object.amount});
        labels[i] = datas[i].map(object => {return object.date});
        percent[i] = 0;

        const colorDefault = areacolors[2];
        color[i] = colorDefault;
        if (amountArray[i].length > 0){
            color[i] = amountArray[i].map(x => colorDefault);
            color[i][argMax(amountArray[i])] = areacolors[4];
            color[i][argMin(amountArray[i])] = areacolors[5];

            console.log(color[i]);

            valueMax[i] = amountArray[i][argMax(amountArray[i])];
            percent[i] = percentageLimitChart(valueMax[i]);
        }

        data[i] = {
            fill: true,
            //fill: false,
            stack:true,
            label: titles[i],
            lineTension:.5,
            backgroundColor:transparentize(areacolors[0]),
            borderColor:areacolors[1],
            pointRadius:4,
            pointBackgroundColor: color[i],
            pointBorderColor:areacolors[3],
            pointHoverRadius:5,
            pointHoverBackgroundColor:"rgba(2,117,216,1)",
            pointHitRadius:20,
            pointBorderWidth:2,
            data: amountArray[i]
        }

    }


    let ctx = document.getElementById(ctxElem).getContext('2d');

    return new Chart(ctx,{
        type:"line",
        data:{
            labels: labels[0],
            datasets: data
        },
        options: {
            responsive: true,
            /*scales: {
                y: {
                    stacked: true
                }
            },*/
            tooltips: {
                callbacks: {
                    label: function (tooltipItem, data) {
                        let label = data.datasets[tooltipItem.datasetIndex].label || '';

                        if (label){
                            label += ': ';
                        }
                        label += formatNumber(tooltipItem.yLabel,thousandSeparator);
                        return label;
                    }
                }
            },
            plugins:{
                legend: {
                    display: true
                }
            }
        }
    });
}

function loadBarChart(ctxElem, datas,title,thousandSeparator,locale="en",colors=[]){
    Chart.defaults.defaultFontFamily='-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.defaultFontColor="#292b2c";

    let ctx = document.getElementById(ctxElem).getContext('2d');

    let amountArray = datas.map(object => {
        return object.amount;
    });

    let percent = 0;
    if (amountArray.length > 0){
        const valueMax = amountArray[argMax(amountArray)];
        percent = percentageLimitChart(valueMax);
    }

    return new Chart(ctx,
        {
            type:"bar",
            data:{
                labels:allMonth(locale),
                datasets:[
                    {
                        label: title,
                        backgroundColor:colors[0],
                        borderColor:colors[1],
                        data:datas.map(object => {
                            return object.amount;
                        })
                    }
                ]
            },
            options:{
                scales:{
                    x:{
                        time:{unit:"month"},
                        gridLines:{display:!1},
                        ticks:{z:12},
                        fontSize: 12
                    },
                    y:{
                        min:0,
                        max:percent,
                        ticks:{z:5},
                        fontSize: 12,
                        callback: function(value){
                            return formatNumber(value,thousandSeparator);
                        },
                        gridLines:{display:!0}
                    }
                },
                tooltips: {
                    callbacks: {
                        label: function (tooltipItem, data) {
                            let label = data.datasets[tooltipItem.datasetIndex].label || '';

                            if (label){
                                label += ': ';
                            }
                            label += formatNumber(tooltipItem.yLabel,thousandSeparator);
                            return label;
                        }
                    }
                },
                plugins:{
                    legend: {
                        display: (colors[2]==='true')
                    }
                }
            }
        });
}

function loadDoughnutOrPieChart(type,ctxElem, datas,thousandSeparator,colors=[]){
    Chart.defaults.defaultFontFamily='-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    Chart.defaults.defaultFontColor="#292b2c";

    const qtyArray = datas.map(object => {return object.qty});

    let ctx = document.getElementById(ctxElem).getContext('2d');
    return new Chart(ctx,{
        type:type,
        data:{
            labels: datas.map(object => {return object.name}),
            datasets:[
                {
                    backgroundColor:doughnutcolors[0],
                    hoverOffset: 4,
                    data: qtyArray
                }
            ]
        },
        options: {
            aspectRatio: 1,
            tooltips: {
                callbacks: {
                    label: function (tooltipItem, data) {
                        console.log(tooltipItem);
                        let label = data.labels[tooltipItem.index] || '';

                        if (label){
                            label += ': ';
                        }
                        label += formatNumber(data.datasets[0].data[tooltipItem.index],thousandSeparator);
                        return label;
                    }
                }
            },
            plugins:{
                legend: {
                    display: (doughnutcolors[0]==='true')
                }
            }
        }
    });
}
