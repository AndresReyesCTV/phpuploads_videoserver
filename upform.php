<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>selective uploader</title>
    <style>
button#uploader {
    height: 3em;
    width: 14em;
    margin: auto;
    display: block;
    font-size: 14.4px;
    text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
    background: #107FC9;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.4),0 2px 0 #0d5e94;
    border-color: #0d5e94;
    color: #fff;
}


input#f {display : none;}
    </style>



</head>
<body>
    <form method="post" action="/phpuploads/uploader.php" enctype="multipart/form-data" onsubmit="return false;">

        <span class="fileinput-btn" style='height:4em;'>
            <button id="uploader" class="btn primary big fileinput-label js-file-import-button" style='height:3em;width:14em;'>
                <span class="sprite-img v-middle button-icon upCloud white"></span>
 Subir archivo
            </button>
            <input id   = "f" type="file"
                   name = "f"
                   class = "js-file-import-button"
                   />
        </span>
    </form> 
<script>

(function() {
    const idInstitution = <?=$_REQUEST["idinstitution"]?>,
          shortname     = '<?=$_REQUEST["shortname"]?>';
    let chunkNumber = 0;



    function send(file, size, chunkSize, start, end, filename, estimatedChunks) {
        const formdata = new FormData();
        const xhr      = new XMLHttpRequest();
         
        if (size - end < 0) {
            end = size;
        }
        const slicedPart = file.slice(start, end);

        

        if (estimatedChunks < chunkNumber) {
            const endData = new FormData();
            const xhr2 = new XMLHttpRequest();

            endData.append('filename', filename);
            endData.append('estimated', estimatedChunks);
            endData.append('idInstitution', idInstitution);
            endData.append('shortname', shortname);
            xhr2.onreadystatechange = function () {
                if (xhr2.readyState == XMLHttpRequest.DONE) {
                    console.log(xhr.responseText);
                }

            };
            xhr2.open('POST', '/phpuploads/passtoapi.php', true);
            xhr2.send(endData);
            chunkNumber = 0;

        } else {
           xhr.onreadystatechange = function () {
                if (xhr.readyState == XMLHttpRequest.DONE) {
                    console.log('Done Sending Chunk ' + chunkNumber + ' ' + xhr.responseText);
                    chunkNumber++;
                    setTimeout(
                        function() {
                            send(file, size, chunkSize, end, end + chunkSize, filename, estimatedChunks);
                        },
                        50
                    )
                }
            }

            formdata.append('filename', filename);
            formdata.append('estimated', estimatedChunks);
            formdata.append('start', start);
            formdata.append('end', end);
            formdata.append('number', chunkNumber);
            formdata.append('chunk', slicedPart);
            formdata.append('chunkSize', chunkSize);
            xhr.open('POST', '/phpuploads/uploader.php', true);

            console.log('Sending Chunk (Start - End): ' + start + ' ' + end);
            xhr.send(formdata);
        }
    }


    const f = document.getElementById('f');
    
    f.addEventListener('change', function(e) {
 
          if (f.files.length > 0) {
            const file = f.files[0],
                  size  = file.size;

            if (['mov', 'mp4', 'm4a', '3gp', '3g2', 'flv', 'webm', 'wmv'].indexOf(file.name.split('.').pop().toLowerCase() ) > -1) { 

                const chunk = 5020000,
                  start = 0,
                  filename = file.name,
                   estimatedChunks = Math.floor(size / chunk) + 1;

                send(file, size, chunk, start, chunk, filename, estimatedChunks);
            }
        }
    } );
    

})();

</script>    
    
</body>
</html>

