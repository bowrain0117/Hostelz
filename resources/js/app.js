import './bootstrap';

Echo.private('import-inserted').listen('.import.inserted', e => {
  // console.log(e, e.data);
})

Echo.channel('import-started').listen('.import.started', e => {
  // console.log(e, e.data);
})

Echo.channel('import-page-added').listen('.import.page.added', e => {
  // console.log(e, e.data);
})