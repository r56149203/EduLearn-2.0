<?php
// Quill Editor Component - Reusable across all admin forms
function initQuillEditor($fieldId, $height = 200, $placeholder = 'Enter content here...') {
    return '
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <link href="' . SITE_URL . 'assets/css/quill-custom.css" rel="stylesheet">
    
    <div id="editor-container-' . $fieldId . '" style="height: ' . $height . 'px; margin-bottom: 50px;">
        <div id="' . $fieldId . '"></div>
    </div>
    <textarea name="' . $fieldId . '" id="' . $fieldId . '-textarea" style="display:none;"></textarea>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var toolbarOptions = [
            ["bold", "italic", "underline", "strike"],
            ["blockquote", "code-block"],
            [{ "header": 1 }, { "header": 2 }],
            [{ "list": "ordered"}, { "list": "bullet" }],
            [{ "script": "sub"}, { "script": "super" }],
            [{ "indent": "-1"}, { "indent": "+1" }],
            [{ "direction": "rtl" }],
            [{ "size": ["small", false, "large", "huge"] }],
            [{ "header": [1, 2, 3, 4, 5, 6, false] }],
            [{ "color": [] }, { "background": [] }],
            [{ "font": [] }],
            [{ "align": [] }],
            ["clean", "link", "image", "video", "formula"]
        ];
        
        var quill = new Quill("#' . $fieldId . '", {
            modules: {
                toolbar: toolbarOptions
            },
            placeholder: "' . $placeholder . '",
            theme: "snow"
        });
        
        // Handle image uploads
        quill.getModule("toolbar").addHandler("image", function() {
            var input = document.createElement("input");
            input.setAttribute("type", "file");
            input.setAttribute("accept", "image/*");
            input.click();
            
            input.onchange = function() {
                var file = this.files[0];
                var formData = new FormData();
                formData.append("image", file);
                
                fetch("' . SITE_URL . 'api/upload-image.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var range = quill.getSelection();
                        quill.insertEmbed(range.index, "image", data.url);
                    } else {
                        alert("Image upload failed: " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("Failed to upload image");
                });
            };
        });
        
        // Save content to textarea before form submission
        var form = quill.container.closest("form");
        if (form) {
            form.addEventListener("submit", function() {
                var html = quill.root.innerHTML;
                document.getElementById("' . $fieldId . '-textarea").value = html;
            });
        }
        
        // Load existing content if any
        var existingContent = document.getElementById("' . $fieldId . '-textarea").value;
        if (existingContent) {
            quill.root.innerHTML = existingContent;
        }
    });
    </script>
    ';
}

// Initialize multiple editors on one page
function initMultipleQuillEditors($editors) {
    $script = '
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <link href="' . SITE_URL . 'assets/css/quill-custom.css" rel="stylesheet">
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var toolbarOptions = [
            ["bold", "italic", "underline", "strike"],
            ["blockquote", "code-block"],
            [{ "header": 1 }, { "header": 2 }],
            [{ "list": "ordered"}, { "list": "bullet" }],
            ["link", "image"]
        ];
    ';
    
    foreach ($editors as $id => $options) {
        $height = $options['height'] ?? 150;
        $script .= "
        var quill_{$id} = new Quill('#{$id}', {
            modules: { toolbar: toolbarOptions },
            placeholder: '{$options['placeholder']}',
            theme: 'snow'
        });
        
        quill_{$id}.getModule('toolbar').addHandler('image', function() {
            var input = document.createElement('input');
            input.setAttribute('type', 'file');
            input.setAttribute('accept', 'image/*');
            input.click();
            
            input.onchange = function() {
                var file = this.files[0];
                var formData = new FormData();
                formData.append('image', file);
                
                fetch('" . SITE_URL . "api/upload-image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        var range = quill_{$id}.getSelection();
                        quill_{$id}.insertEmbed(range.index, 'image', data.url);
                    }
                });
            };
        });
        
        var form = quill_{$id}.container.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                var html = quill_{$id}.root.innerHTML;
                document.getElementById('{$id}-textarea').value = html;
            });
        }
        
        var existingContent = document.getElementById('{$id}-textarea').value;
        if (existingContent) {
            quill_{$id}.root.innerHTML = existingContent;
        }
        ";
    }
    
    $script .= '});</script>';
    return $script;
}
?>