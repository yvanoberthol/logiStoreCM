function previewFiles() {

    const preview = document.querySelector('#preview');
    const files   = document.querySelector('input[type=file]').files;

    function readAndPreview(file) {

        // Veillez à ce que `file.name` corresponde à nos critères d’extension
        if ( /\.(jpe?g|png|gif)$/i.test(file.name) ) {
            const reader = new FileReader();

            reader.addEventListener("load", function () {
                const image = new Image();
                image.height = 100;
                image.title = file.name;
                image.src = this.result;
                preview.appendChild( image );
            }, false);

            reader.readAsDataURL(file);
        }

    }

    if (files) {
        [].forEach.call(files, readAndPreview);
    }

}

function previewFile(input,img) {
    const file    = input.files[0];
    const reader  = new FileReader();

    reader.onload = function () {
        img.attr("src", reader.result);
    };

    if (file) {
        reader.readAsDataURL(file);
    }
}