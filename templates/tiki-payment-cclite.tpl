<p>{$payment.amount_paid|escape}&nbsp;{$payment.details.mc_currency|escape} was paid on {$payment.payment_date|escape}.</p>
<p><strong>{tr}Cclite{/tr}</strong> ({$payment.details.info|escape})</p>
<div class="card bg-light">
    <div class="card-body">
        <p>{$payment.details.perform_trade}</p>
    </div>
</div>
