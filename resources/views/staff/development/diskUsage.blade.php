@if (auth()->user()->hasPermission('admin'))
<h4>Root folder usages:</h4>
<pre>
{{ shell_exec('df -h') }}
</pre>

<h4>Production folder usages:</h4>
<pre>
{{ shell_exec('du -h --max-depth=1 /home/hostelz/production | sort -hr') }}
</pre>

<h4>Home folder usages:</h4>
<pre>
{{ shell_exec('du -h --max-depth=1 /home/hostelz | sort -hr') }}
</pre>

<h4>Dev folder usages:</h4>
<pre>
{{ shell_exec('du -h --max-depth=1 /home/hostelz/dev | sort -hr') }}
</pre>
@endif
