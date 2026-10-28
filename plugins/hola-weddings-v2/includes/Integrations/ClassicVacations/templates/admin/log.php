<h1>Classic Vacations Log</h1>
<div id="log" class="bg-white py-3 px-4 border" style="overflow-y: scroll; height: 400px">
    <?= nl2br($log) ?>
</div>
<form id="clear-log" method="post" action="?page=classic-vacations-log" class="mt-3">  
    <input type="hidden" name="action" value="hw_clear_log" />
    <button type="submit" class="btn btn-primary">Clear Log</button>
</form>
