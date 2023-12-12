<div id="attachments" class="mailAttachments" style="margin: 40px 0;">
    <h2>Attachments</h2>

    @include('Lib/fileListHandler', ['requestURL' => route('staff-mail-editAttachments', ['mailID' => $mail->id])])
    <h3>Upload</h3>
    @include('Lib/fileUploadHandler', ['requestURL' => route('staff-mail-editAttachments', ['mailID' => $mail->id])])
</div>