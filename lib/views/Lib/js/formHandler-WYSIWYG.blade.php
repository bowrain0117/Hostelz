tinyMCE.init({
language : "en",
selector: "textarea.wysiwyg",
{{--    theme : "modern",--}}
resize: true, {{-- doesnt seem to actually work unless we turn on the statusbar --}}

{{--    path: false,--}}
statusbar : true,
menubar : true,

plugins : 'searchreplace autolink  visualblocks visualchars fullscreen image link media codesample charmap pagebreak nonbreaking anchor insertdatetime advlist lists wordcount  code',

toolbar: [
"undo redo | link | bullist numlist | bold italic | removeformat code | paste pastetext | tocupdate" ,
"formatselect | alignleft aligncenter alignright alignjustify"
],

target_list: [
//{title: 'None', value: ''},
{title: 'Same tab', value: '_self'},
{title: 'New tab', value: '_blank'},
//{title: 'Parent frame', value: '_parent'}
],
paste_as_text: false, {{-- because totalcommercial users were pasting a bunch of junk html from Word, etc. --}}
paste_word_valid_elements: "b,strong,i,em,h1,h2",
paste_enable_default_filters: false,

link_context_toolbar: true,
default_link_target: "_blank",
link_rel_list: [
{title: 'dofollow', value: ''},
{title: 'nofollow', value: 'nofollow'},
{title: 'alternate', value: 'alternate'}
],
link_class_list: [
{title: 'default', value: ''},
{title: 'Primary Button Large', value: 'btn btn-lg btn-primary rounded px-5'},
],

advlist_number_styles: "lower-alpha lower-greek",
fix_list_elements : true,

content_css: [
'{{ routeURL('generated-css', 'new-styles') }}',
],
});
