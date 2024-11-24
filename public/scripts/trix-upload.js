// {projectRoot}/public/scripts/trix-upload.js
(function () {
    const UPLOAD_URL = "/admin/upload/trix";

    addEventListener('trix-before-initialize', () => {
        Trix.config.attachments.preview.caption = { name: false, size: false }
    });

    addEventListener("trix-file-accept", function (evt) {
        // Prevent attaching anything except images
        if (!evt.file.type.startsWith("image/")) {
            evt.preventDefault()
        }

        // Prevent attaching files > 10MB
        if (evt.file.size > 10_000_000) {
            evt.preventDefault()
        }
    })

    addEventListener("trix-attachment-add", function (evt) {
        if (evt.attachment.file) {
            uploadFileAttachment(evt.attachment)
        }
    })

    function uploadFileAttachment(attachment) {
        const xhr = new XMLHttpRequest()
        xhr.open("POST", UPLOAD_URL, true)
        xhr.responseType = "json";

        xhr.upload.addEventListener(
            "progress",
            evt => {
                attachment.setUploadProgress(evt.loaded / evt.total * 100)
            }
        );

        xhr.addEventListener(
            "load",
            evt => {
                if (xhr.response.ok) {
                    attachment.setAttributes({
                        url: xhr.response.url
                    })
                } else {
                    alert(xhr.response.error || "Error while uploading attachment")
                    console.error("Upload error: ", xhr.response);
                }
            }
        );

        const data = new FormData();
        data.append("file", attachment.file)
        xhr.send(data);
    }
})();