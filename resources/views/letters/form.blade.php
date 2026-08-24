@extends('layouts.app')
@section('title',$letter->exists ? 'Edit Letter' : 'New Letter')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const uploadUrl = @json(route('letters.images.store'));
    const csrfToken = @json(csrf_token());
    const editor = document.getElementById('letterEditor');
    const contentField = document.querySelector('[name="content"]');
    const contentTypeField = document.querySelector('[name="content_type"]');

    const escapeHtml = (value) => value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    const contentToHtml = (content) => {
        if (!content) return '';
        if (/<[a-z][\s\S]*>/i.test(content)) return content;

        return content
            .split(/\n{2,}/)
            .map((paragraph) => `<p>${escapeHtml(paragraph).replace(/\n/g, '<br>')}</p>`)
            .join('');
    };

    const syncEditorToField = () => {
        if (!editor || !contentField) return;
        contentField.value = editor.innerHTML.trim();
        contentTypeField.value = 'html';
    };

    const focusEditor = () => {
        editor?.focus();
    };

    const runCommand = (command, value = null) => {
        focusEditor();
        document.execCommand(command, false, value);
        syncEditorToField();
    };

    const selectionHtml = () => {
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) return '';
        const container = document.createElement('div');
        container.appendChild(selection.getRangeAt(0).cloneContents());

        return container.innerHTML || escapeHtml(selection.toString());
    };

    const replaceSelection = (html) => {
        focusEditor();
        document.execCommand('insertHTML', false, html);
        syncEditorToField();
    };

    const uploadImageFile = (file) => new Promise((resolve, reject) => {
        const formData = new FormData();
        formData.append('file', file);

        fetch(uploadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then(async response => {
                const body = await response.json().catch(() => ({}));
                if (!response.ok || !body.location) {
                    reject(body.message || 'Image upload failed.');
                    return;
                }
                resolve(body.location);
            })
            .catch(() => reject('Image upload failed.'));
    });

    const chooseMediaFile = () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.onchange = () => {
            const file = input.files[0];
            if (!file) return;

            uploadImageFile(file)
                .then((location) => {
                    const imageHtml = `<p><img src="${location}" alt="${file.name}" /></p>`;
                    replaceSelection(imageHtml);
                })
                .catch((message) => alert(message));
        };
        input.click();
    };

    const loadTemplate = (id) => {
        if (!id) return;
        const template = document.querySelector(`#template-${id}`);
        if (!template) return;
        const subject = template.dataset.subject || '';
        const content = template.dataset.content || '';
        const type = template.dataset.type || 'General';
        if (!document.querySelector('[name="subject"]').value || confirm('Load template? This will replace current content.')) {
            document.querySelector('[name="subject"]').value = subject;
            editor.innerHTML = contentToHtml(content);
            syncEditorToField();
            document.querySelector('[name="type"]').value = type;
        }
    };

    const templateSelect = document.querySelector('[name="letter_template_id"]');
    if (templateSelect) {
        templateSelect.addEventListener('change', (e) => loadTemplate(e.target.value));
    }

    const typeSelect = document.querySelector('[name="type"]');
    if (typeSelect) {
        document.querySelectorAll('[data-filter-type]').forEach(el => {
            const filter = () => {
                const val = typeSelect.value;
                document.querySelectorAll('[data-template-item]').forEach(item => {
                    const t = item.dataset.templateType || '';
                    item.style.display = (!val || t === val || t === 'General') ? '' : 'none';
                });
            };
            typeSelect.addEventListener('change', filter);
            filter();
        });
    }

    const insertPlaceholder = (placeholder) => {
        replaceSelection(placeholder);
    };

    window.insertPlaceholder = insertPlaceholder;

    if (editor && contentField) {
        editor.innerHTML = contentToHtml(contentField.value);
        editor.addEventListener('input', syncEditorToField);
        editor.addEventListener('blur', syncEditorToField);
    }

    const addMediaButton = document.getElementById('addMediaButton');
    if (addMediaButton) {
        addMediaButton.addEventListener('click', chooseMediaFile);
    }

    document.querySelectorAll('[data-editor-command]').forEach((button) => {
        button.addEventListener('click', () => {
            const command = button.dataset.editorCommand;
            if (command === 'createLink') {
                const url = prompt('Enter link URL');
                if (url) runCommand('createLink', url);
                return;
            }

            if (command === 'formatBlock') {
                runCommand('formatBlock', button.dataset.editorValue);
                return;
            }

            if (command === 'insertTable') {
                replaceSelection('<table><tbody><tr><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td>&nbsp;</td><td>&nbsp;</td></tr></tbody></table><p></p>');
                return;
            }

            runCommand(command);
        });
    });

    const blockFormat = document.getElementById('letterBlockFormat');
    if (blockFormat) {
        blockFormat.addEventListener('change', () => runCommand('formatBlock', blockFormat.value));
    }

    const lineHeight = document.getElementById('letterLineHeight');
    if (lineHeight) {
        lineHeight.addEventListener('change', () => {
            const html = selectionHtml() || '&nbsp;';
            replaceSelection(`<span style="line-height:${lineHeight.value};">${html}</span>`);
        });
    }

    document.querySelectorAll('[data-editor-color]').forEach((input) => {
        input.addEventListener('input', () => runCommand(input.dataset.editorColor, input.value));
    });

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', syncEditorToField);
    }
});
</script>
@endpush
@section('content')
<style>
    .letter-editor-shell {
        border: 1px solid #d0d7de;
        background: #f6f7f7;
        margin-top: .5rem;
    }
    .letter-editor-media-row {
        padding: 8px 8px 6px;
        border-bottom: 1px solid #d0d7de;
        background: #f6f7f7;
    }
    .letter-add-media {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        min-height: 40px;
        padding: 8px 12px;
        border: 1px solid #3b63ff;
        border-radius: 2px;
        background: #fff;
        color: #2451ff;
        font-weight: 600;
    }
    .letter-add-media:hover {
        color: #173bd6;
        border-color: #173bd6;
        background: #f8faff;
    }
    .letter-editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
        padding: 8px;
        border-bottom: 1px solid #d0d7de;
        background: #f6f7f7;
    }
    .letter-editor-toolbar .form-select {
        width: auto;
        min-width: 150px;
        border-radius: 0;
        background-color: #fff;
    }
    .letter-tool {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 34px;
        border: 1px solid transparent;
        border-radius: 2px;
        background: transparent;
        color: #3f4a54;
        font-size: 18px;
    }
    .letter-tool:hover,
    .letter-tool:focus {
        border-color: #c9d1d9;
        background: #fff;
        color: #111827;
    }
    .letter-color {
        width: 38px;
        height: 34px;
        padding: 2px;
        border: 1px solid transparent;
        background: transparent;
    }
    .letter-editor-canvas {
        min-height: 520px;
        padding: 18px;
        background: #fff;
        outline: none;
        font-size: 14px;
        line-height: 1.65;
        color: #1f2937;
    }
    .letter-editor-canvas:focus {
        box-shadow: inset 0 0 0 2px rgba(59, 99, 255, .12);
    }
    .letter-editor-canvas img {
        max-width: 100%;
        height: auto;
    }
    .letter-editor-canvas table {
        width: 100%;
        border-collapse: collapse;
    }
    .letter-editor-canvas td,
    .letter-editor-canvas th {
        border: 1px solid #d1d5db;
        padding: 6px;
    }
    .letter-editor-canvas blockquote {
        border-left: 3px solid #00A651;
        margin-left: 0;
        padding-left: 12px;
        color: #4b5563;
    }
</style>
<form method="post" action="{{ $letter->exists ? route('letters.update',$letter) : route('letters.store') }}">
    @csrf
    @if($letter->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card"><div class="card-body">
                <div class="row g-2">
                    @unless($letter->exists)<div class="col-md-3"><label class="form-label">Prefix</label><input class="form-control" name="prefix" value="{{ old('prefix',$letter->prefix ?: 'LTR') }}" required></div>@endunless
                    <div class="col-md-{{ $letter->exists ? 6 : 5 }}"><label class="form-label">Type</label><select class="form-select" name="type">@foreach($types as $type)<option value="{{ $type }}" @selected(old('type',$letter->type)===$type)>{{ $type }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status">@foreach($statuses as $status)<option value="{{ $status }}" @selected(old('status',$letter->status)===$status)>{{ $status }}</option>@endforeach</select></div>
                </div>

                <label class="form-label mt-3">Template</label>
                <select class="form-select" name="letter_template_id" @change="loadTemplate">
                    <option value="">No template</option>
                    @foreach($templates->groupBy('type') as $groupType => $groupTemplates)
                        <optgroup label="{{ $groupType }}">
                            @foreach($groupTemplates as $template)
                                <option value="{{ $template->id }}" data-template-item data-template-type="{{ $template->type }}" {{-- stored data for JS --}}
                                    @selected(old('letter_template_id',$letter->letter_template_id)===$template->id)
                                    data-subject="{{ $template->default_subject ?: $template->name }}"
                                    data-content="{{ $template->content }}"
                                    data-type="{{ $template->type }}"
                                    id="template-{{ $template->id }}"
                                >{{ $template->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>

                <label class="form-label mt-3">Subject</label>
                <input class="form-control" name="subject" value="{{ old('subject',$letter->subject) }}" required>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <label class="form-label mb-0">Content</label>
                </div>

                <div class="letter-editor-shell">
                    <div class="letter-editor-media-row">
                        <button type="button" id="addMediaButton" class="letter-add-media">
                            <i class="bi bi-images"></i>
                            Add Media
                        </button>
                    </div>
                    <div class="letter-editor-toolbar" aria-label="Letter formatting tools">
                        <select id="letterBlockFormat" class="form-select form-select-sm" title="Paragraph format">
                            <option value="p">Paragraph</option>
                            <option value="h1">Heading 1</option>
                            <option value="h2">Heading 2</option>
                            <option value="h3">Heading 3</option>
                            <option value="pre">Preformatted</option>
                        </select>
                        <button type="button" class="letter-tool" data-editor-command="bold" title="Bold"><strong>B</strong></button>
                        <button type="button" class="letter-tool" data-editor-command="italic" title="Italic"><em>I</em></button>
                        <button type="button" class="letter-tool" data-editor-command="underline" title="Underline"><u>U</u></button>
                        <button type="button" class="letter-tool" data-editor-command="strikeThrough" title="Strikethrough"><s>S</s></button>
                        <button type="button" class="letter-tool" data-editor-command="insertUnorderedList" title="Bullet list"><i class="bi bi-list-ul"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="insertOrderedList" title="Numbered list"><i class="bi bi-list-ol"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="formatBlock" data-editor-value="blockquote" title="Quote"><i class="bi bi-quote"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="justifyLeft" title="Align left"><i class="bi bi-text-left"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="justifyCenter" title="Align center"><i class="bi bi-text-center"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="justifyRight" title="Align right"><i class="bi bi-text-right"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="justifyFull" title="Justify"><i class="bi bi-justify"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="createLink" title="Insert link"><i class="bi bi-link-45deg"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="insertHorizontalRule" title="Horizontal line"><i class="bi bi-hr"></i></button>
                        <button type="button" class="letter-tool" data-editor-command="insertTable" title="Insert table"><i class="bi bi-table"></i></button>
                        <select id="letterLineHeight" class="form-select form-select-sm" title="Line height">
                            <option value="1.2">Line</option>
                            <option value="1.4">1.4</option>
                            <option value="1.6" selected>1.6</option>
                            <option value="2">2.0</option>
                        </select>
                        <input type="color" class="letter-color" data-editor-color="foreColor" value="#1f2937" title="Text color">
                        <input type="color" class="letter-color" data-editor-color="backColor" value="#ffffff" title="Highlight color">
                    </div>
                    <input type="hidden" name="content_type" value="{{ old('content_type', $letter->content_type ?: 'html') }}">
                    <textarea class="d-none" name="content" id="content-editor">{{ old('content',$letter->content) }}</textarea>
                    <div id="letterEditor" class="letter-editor-canvas" contenteditable="true" role="textbox" aria-label="Letter content"></div>
                </div>

                <div class="d-flex flex-wrap gap-1 mt-2">
                    <span class="text-muted small me-2">Variables:</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}client_name}}')">Client</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}client_company}}')">Company</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}contact_person}}')">Contact</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}site_name}}')">Site</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}project_name}}')">Project</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}invoice_number}}')">Invoice</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}invoice_total}}')">Total</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}invoice_balance}}')">Balance</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}today_date}}')">Date</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}company_name}}')">My Co</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertPlaceholder('{{'{{'}}prepared_by}}')">Signatory</button>
                </div>

                <button class="btn btn-warning mt-3">Save Letter</button>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card"><div class="card-body">
                <h2 class="h5">Links</h2>
                <label class="form-label">Client</label><select class="form-select mb-2" name="client_id"><option value="">Client</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected(old('client_id',$letter->client_id)===$client->id)>{{ $client->name }}</option>@endforeach</select>
                <label class="form-label">Site</label><select class="form-select mb-2" name="site_id"><option value="">Site</option>@foreach($sites as $site)<option value="{{ $site->id }}" @selected(old('site_id',$letter->site_id)===$site->id)>{{ $site->site_name }}</option>@endforeach</select>
                <label class="form-label">Project</label><select class="form-select mb-2" name="project_id"><option value="">Project</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(old('project_id',$letter->project_id)===$project->id)>{{ $project->project_name }}</option>@endforeach</select>
                <label class="form-label">Invoice</label><select class="form-select mb-2" name="invoice_id"><option value="">Invoice</option>@foreach($invoices as $invoice)<option value="{{ $invoice->id }}" @selected(old('invoice_id',$letter->invoice_id)===$invoice->id)>{{ $invoice->invoice_number }}</option>@endforeach</select>
                <label class="form-label">Receipt</label><select class="form-select mb-2" name="receipt_id"><option value="">Receipt</option>@foreach($receipts as $receipt)<option value="{{ $receipt->id }}" @selected(old('receipt_id',$letter->receipt_id)===$receipt->id)>{{ $receipt->receipt_number }}</option>@endforeach</select>
            </div></div>

            @if($signatories?->count())
            <div class="card mt-3"><div class="card-body">
                <h2 class="h5">Default Signatory</h2>
                @foreach($signatories as $sig)
                    <div class="d-flex align-items-center gap-2 py-1">
                        @if($sig->signatureUrl())
                            <img src="{{ $sig->signatureUrl() }}" style="max-height:32px;">
                        @endif
                        <div>
                            <strong>{{ $sig->name }}</strong>
                            <div class="small text-muted">{{ $sig->title }}</div>
                        </div>
                        @if($sig->is_default)<span class="badge bg-warning ms-auto">Default</span>@endif
                    </div>
                @endforeach
            </div></div>
            @endif
        </div>
    </div>
</form>
@endsection
