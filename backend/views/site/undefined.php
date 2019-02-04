<?php

use backend\models\Node;

?>

<?=Node::hideBreadcrumbs()?>

<p class="h3">You should add this currencies to CurrencyList!!</p>
<ul class="list-group">
    <?php foreach ($undefinedCurrencies as $method => $currencies): ?>
        <p class="h4"><?= ucfirst($method) ?></p>
        <?php foreach ($currencies as $key => $currency): ?>
            <li class="list-group-item alert-error"><?= $currency ?></li>
        <?php endforeach; ?>
    <?php endforeach; ?>
</ul>

<hr>

<p class="h3">You should add for each payment method a image to <b>payment_method_lib</b> table!!</p>
<ul class="list-group">
    <?php foreach ($methodsWithoutImage as $method): ?>
        <li class="list-group-item alert-error"><?= ucfirst($method) ?></li>
    <?php endforeach; ?>
</ul>