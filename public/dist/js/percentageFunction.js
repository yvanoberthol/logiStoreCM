function percentageLimitChart(value,subvalue = 2) {

    const lengthValue = parseInt(value).toString().length;
    console.log(lengthValue);

    let zeroString = '';
    for (let i = 0; i < (lengthValue - subvalue); i++){
        zeroString += '0';
    }

    const numberMultiplier = '1'+zeroString;

    const valueToSub = value.toString().substring(0,subvalue);

    let valueSubNumber = parseInt(valueToSub);

    if (valueSubNumber % 5 !== 0){
        valueSubNumber = (valueSubNumber - (valueSubNumber % 5)) + 10;
    }

    let valueMultiplier = valueSubNumber * parseInt(numberMultiplier);

    if (value >= valueMultiplier){
        valueMultiplier = (valueSubNumber + 10) * parseInt(numberMultiplier)
    }

    return valueMultiplier;
}
