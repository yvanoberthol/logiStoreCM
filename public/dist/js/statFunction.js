// retourne les sales par mois
function saleByMonth(sales, month, year){
    return sales.map(sale => {
        if (new Date(sale.dateNotFormated).getUTCMonth() === month &&
            new Date(sale.dateNotFormated).getFullYear() === year){
            return {
                date: sale.date.toLocaleString(),
                amount: sale.amount
            };
        }
        return null;
    }).filter(sale => sale !== null)
}

// recupérer les amounts des sales mensuelles par année
function saleByYear(sales,year){
    let amountByMonth = [];
    allMonth().forEach((month, index) =>{
        let amountMonth = sales.map(sale => {
            if (new Date(sale.dateNotFormated).getUTCMonth() === index &&
                new Date(sale.dateNotFormated).getFullYear() === year)
                return sale.amount;
            return 0;
        }).reduce(function (total,amount) {
            return total + amount;
        },0);

        amountByMonth.push({
            month: month,
            amount: amountMonth
        });
    });
    return amountByMonth;
}




function saleByYearRate(sales,year){
    let amountByMonth = [];
    allMonth().forEach((month, index) =>{
        let nbDays = 0;
        let amountMonth = sales.map(sale => {
            if (new Date(sale.date).getUTCMonth() === index &&
                new Date(sale.date).getFullYear() === year){
                nbDays++;
                return sale.amount;
            }
            return 0;
        }).reduce(function (total,amount) {
            return total + amount;
        },0);

        let amount = Number((amountMonth/nbDays)).toFixed(2);
        if(isNaN(amount))
            amount = 0;

        amountByMonth.push({
            month: month,
            amount: amount
        });
    });
    return amountByMonth;
}
