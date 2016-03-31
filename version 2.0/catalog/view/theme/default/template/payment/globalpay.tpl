<form id="payment" action="<?php echo $gateway_url; ?>" method="post">
    <?php echo $globalpay_hidden_args; ?>
    <div class="buttons">
        <div class="right">
            <input type="submit" value="<?php echo $button_confirm; ?>" class="button" />
        </div>
    </div>
</form>
