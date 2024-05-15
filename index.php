<!DOCTYPE html>
<html>
<head>
    <title>의료 진료 카드 작성</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.10.377/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <!-- <script src="signature_pad.min.js"></script> -->
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        #pdfViewer {
            position: relative;
            width: 900px;
            height: 1273px; /* A4 용지 비율에 맞춰 높이 설정 */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        #signature-pad-wrap {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none; /* 초기에는 상호작용을 방지 */
            z-index: 10; /* PDF 캔버스 위에 오도록 설정 */
            border:1px solid #000
        }
        #signature-pad{border:1px solid red}
        .signature-pad--footer{
            position:fixed;
            bottom:0;
            left:0;
            width:100%;
            text-align:center;
        }
    </style>
</head>
<body>
    <div id="pdfViewer">
        <canvas id="pdf-canvas"></canvas>

        <div class='signature-pad-wrap'>
            <div id="signature-pad" class="signature-pad">
                <button type='button' class='signature-pad-close'><i class='fa fa-times'></i></button>

                <div class="signature-pad--body">
                    <canvas></canvas>
                </div>

                <div class="signature-pad--footer">
                    <div class="description">Sign above</div>

                    <div class="signature-pad--actions">
                        <div>
                            <button type="button" class="btn btn-sm btn-default sign_clear" data-action="clear">Clear</button>
                            <button type="button" class="btn btn-sm btn-default" data-action="undo">Undo</button>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary save" data-action="save-data" data-target=''>Save</button>
                            <!-- <button type="button" class="btn btn-sm btn-default save" data-action="save-png">Save as PNG</button>
                            <button type="button" class="btn btn-sm btn-default save" data-action="save-jpg">Save as JPG</button>
                            <button type="button" class="btn btn-sm btn-default save" data-action="save-svg">Save as SVG</button> -->
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <script>
        const pdfViewerElement = document.getElementById('pdfViewer');
        const pdfCanvas = document.getElementById('pdf-canvas');
        // const signaturePadCanvas = document.getElementById('signature-pad');

        let pdfDoc = null;

        // document.getElementById('save-signature').addEventListener('click', () => {
        //     // 사인 캔버스의 상호작용 토글
        //     if (signaturePadCanvas.style.pointerEvents === 'none') {
        //         signaturePadCanvas.style.pointerEvents = 'auto';
        //         document.getElementById('save-signature').textContent = '사인 완료';
        //     } else {
        //         signaturePadCanvas.style.pointerEvents = 'none';
        //         document.getElementById('save-signature').textContent = '사인 저장';
        //     }
        // });

        // document.getElementById('submit').addEventListener('click', async () => {
        //     // 사인을 위한 상호작용을 비활성화
        //     signaturePadCanvas.style.pointerEvents = 'none';

        //     // 사인 캔버스를 PDF 캔버스에 결합
        //     const pdfContext = pdfCanvas.getContext('2d');
        //     pdfContext.drawImage(signaturePadCanvas, 0, 0);

        //     // 결합된 캔버스를 데이터 URL로 변환하여 서버에 업로드
        //     const combinedData = pdfCanvas.toDataURL('image/png');

        //     const formData = new FormData();
        //     formData.append('signedPdf', combinedData);

        //     fetch('upload.php', {
        //         method: 'POST',
        //         body: formData
        //     }).then(response => response.text())
        //       .then(data => console.log(data));
        // });

        // // PDF.js로 PDF 불러오기
        async function loadPdfForm() {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type') || 'default';
            const pdfPath = `/path/to/pdf/forms/${type}.pdf`;

            const pdfjsLib = window['pdfjs-dist/build/pdf'];
            pdfDoc = await pdfjsLib.getDocument(pdfPath).promise;
            const page = await pdfDoc.getPage(1);
            const viewport = page.getViewport({ scale: 1.52 });
            console.log(page.getViewport(1.0));

            pdfCanvas.height = pdfViewerElement.offsetHeight;
            pdfCanvas.width = pdfViewerElement.offsetWidth;
            const ctx = pdfCanvas.getContext('2d');

            const renderContext = {
                canvasContext: ctx,
                viewport: viewport
            };
            await page.render(renderContext).promise;
        }

        loadPdfForm();


        // signaturePadCanvas
        
        var wrapper = document.getElementById("signature-pad");
        var clearButton = wrapper.querySelector("[data-action=clear]");
        var changeColorButton = wrapper.querySelector("[data-action=change-color]");
        var undoButton = wrapper.querySelector("[data-action=undo]");
        var saveDATAButton = wrapper.querySelector("[data-action=save-data]");
        var savePNGButton = wrapper.querySelector("[data-action=save-png]");
        var saveJPGButton = wrapper.querySelector("[data-action=save-jpg]");
        var saveSVGButton = wrapper.querySelector("[data-action=save-svg]");
        var canvas = wrapper.querySelector("canvas");
        var signaturePad = new SignaturePad(canvas, {
            // It's Necessary to use an opaque color when saving image as JPEG;
            // this option can be omitted if only saving as PNG or SVG
            backgroundColor: 'rgba(255, 255, 255, 0)'
        });

        // Adjust canvas coordinate space taking into account pixel ratio,
        // to make it look crisp on mobile devices.
        // This also causes canvas to be cleared.
        function resizeCanvas()
        {
            // When zoomed out to less than 100%, for some very strange reason,
            // some browsers report devicePixelRatio as less than 1
            // and only part of the canvas is cleared then.
            var ratio =  Math.max(window.devicePixelRatio || 1, 1);

            // This part causes the canvas to be cleared
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);

            // This library does not listen for canvas changes, so after the canvas is automatically
            // cleared by the browser, SignaturePad#isEmpty might still return false, even though the
            // canvas looks empty, because the internal data of this library wasn't cleared. To make sure
            // that the state of this library is consistent with visual state of the canvas, you
            // have to clear it manually.
            signaturePad.clear();
        }

        // On mobile devices it might make more sense to listen to orientation change,
        // rather than window resize events.
        window.onresize = resizeCanvas;
        resizeCanvas();
        

        clearButton.addEventListener("click", function (event) {
        signaturePad.clear();
    });

    undoButton.addEventListener("click", function (event) {
        var data = signaturePad.toData();

        if (data)
        {
            data.pop(); // remove the last dot or line
            signaturePad.fromData(data);
        }
    });

    saveDATAButton.addEventListener("click", function (event) {
        var dataURL = '',
            el = $('.signature-pad .save').data('target');

        if (signaturePad.isEmpty())
        {
            alert("Please provide a signature first.");
            dataURL = '';
        }
        else
        {
            dataURL = signaturePad.toDataURL();
        }

        $("textarea[name="+el+"]").val(dataURL);
        $("."+el+"_view_wrap").show();
        $("."+el+"_view").attr('src', dataURL);
        $('.signature-pad-close').trigger('click');
    });
    </script>
</body>
</html>