<?php echo $header; ?><?php echo $column_left; ?>
<div style="text-align: center;">
    <?php if($status == 'successful') { ?>
    <b>Transaction Successful</b>
    <p><b>Your Payment Has Been Received</b></p>
    <p>You can view your Purchase History from your "Account Page"</p>
    <p><b>Transaction Reference: </b><?php echo $pmt_txnref; ?></p>
    <div class="buttons">
        <div class="right"><a href="<?php echo $continue; ?>" class="btn btn-primary"><?php echo $button_continue; ?></a></div>
    </div>
    <?php } else{ ?>
    <b>Your Transaction was not Successful</b>
    <p>Payment for this order was not received.</p>
    <?php if ($pmt_txnref !=null){ ?>
    p><b>Transaction Reference: </b><?php echo $pmt_txnref; ?><br />
    <?php } ?> 
    <?php if ($description !=null) { ?>
    <p><b>Reason: </b><?php echo $description; ?><p>
        <?php } ?> 
    <div class="buttons">
        <div class="right"><a class="button" href="<?php echo $fail_continue; ?>"><?php echo $button_continue; ?></a></div>
    </div>
</div>
<?php }?>
<?php echo $column_right; ?>
<?php echo $content_bottom; ?></div>
<?php echo $footer; ?>