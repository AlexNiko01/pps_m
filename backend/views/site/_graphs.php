<div class="row">
    <div class="col-lg-4 col-xs-6">
        <div class="statuses">
            <?php if (!empty($countOfDepositStatuses)): ?>
                <canvas id="deposit-statuses" width="400" height="300"></canvas>
                <input type="button" class="deposit-statuses btn btn-link btn-xs" value="Show Details">
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-2">
    </div>
    <div class="col-lg-4 col-xs-6">
        <div class="statuses">
            <?php if (!empty($countOfWithdrawStatuses)): ?>
                <canvas id="withdraw-statuses" width="400" height="300"></canvas>
                <input type="button" class="withdraw-statuses btn btn-link btn-xs" value="Show Details">
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="statuses">
            <h4 class="text-center" style="padding: 4px;">Count of deposit transactions for 1 day</h4>
            <?php if (!empty($countOfDepositTxsByMinutes)): ?>
                <canvas id="txs-by-minutes-deposit" width="900" height="260"></canvas>
            <?php else: ?>
                <h5 class="text-center" style="padding: 20px;">Deposit transactions not found</h5>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="statuses">
            <h4 class="text-center" style="padding: 4px;">Count of withdraw transactions for last 30 minutes days</h4>
            <?php if (!empty($countOfWithdrawTxsByMinutes)): ?>
                <canvas id="txs-by-minutes-withdraw" width="900" height="260"></canvas>
            <?php else: ?>
                <h5 class="text-center" style="padding: 20px;">Withdraw transactions not found</h5>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($countOfDepositStatuses)): ?>
    <script>
        String.prototype.capitalize = function () {
            return this.charAt(0).toUpperCase() + this.slice(1);
        };

        function ifEmptyStatuses(type) {
            let pie = document.getElementById(type + "-statuses");
            let p = document.createElement('p');
            let text = type + ' statuses not found';
            p.innerText = text.capitalize();
            pie.parentElement.prepend(p);
        }

        function changeDetailView(e, char) {
            char.options.legend.display = !char.options.legend.display;
            if (char.options.legend.display) {
                e.target.value = 'Hide Details';
            } else {
                e.target.value = 'Show Details';
            }
            char.update();
        }

        let deposit = new Chart(document.getElementById("deposit-statuses").getContext('2d'), {
            type: 'pie',
            data: {
                labels: JSON.parse('<?=json_encode(array_keys($countOfDepositStatuses))?>'),
                datasets: [{
                    label: '# of Votes',
                    data: JSON.parse('<?=json_encode(array_values($countOfDepositStatuses))?>'),
                    backgroundColor: [
                        '#95a5a6',
                        'grey',
                        '#3498db',
                        '#27ae60',
                        '#9b59b6',
                        '#e67e22',
                        '#e74c3c',
                        'brown',
                        'green',
                        'tomato'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                cutoutPercentage: 25,
                title: {
                    display: true,
                    text: 'Deposit statuses',
                    fontSize: 16
                },
                legend: {
                    display: false,
                    labels: {
                        boxWidth: 20
                    }
                }
            }
        });

    </script>

<?php endif; ?>
<?php if (!empty($countOfWithdrawStatuses)): ?>
    <script>
        let withdraw = new Chart(document.getElementById("withdraw-statuses").getContext('2d'), {
            type: 'pie',
            data: {
                labels: JSON.parse('<?=json_encode(array_keys($countOfWithdrawStatuses))?>'),
                datasets: [{
                    label: '# of Votes',
                    data: JSON.parse('<?=json_encode(array_values($countOfWithdrawStatuses))?>'),
                    backgroundColor: [
                        '#95a5a6',
                        'grey',
                        '#3498db',
                        '#27ae60',
                        '#9b59b6',
                        '#e67e22',
                        '#e74c3c',
                        'brown',
                        'green',
                        'tomato'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                cutoutPercentage: 25,
                title: {
                    display: true,
                    text: 'Withdraw statuses',
                    fontSize: 16
                },
                legend: {
                    display: false,
                    labels: {
                        boxWidth: 20
                    }
                }
            }
        });
    </script>

<?php endif; ?>
<script>
    if (document.querySelector('.deposit-statuses') != null) {
        document.querySelector('.deposit-statuses').addEventListener('click', (e) => {
            changeDetailView(e, deposit);
        });
    }

    if (document.querySelector('.withdraw-statuses') != null) {
        document.querySelector('.withdraw-statuses').addEventListener('click', (e) => {
            changeDetailView(e, withdraw);
        });
    }
</script>
<?php if (!empty($countOfDepositTxsByMinutes)): ?>
    <script>
        new Chart(document.getElementById("txs-by-minutes-deposit").getContext('2d'), {
            type: 'line',
            data: {
                labels: JSON.parse('<?=json_encode(array_keys($countOfDepositTxsByMinutes))?>'),
                datasets: [
                    {
                        label: 'Deposit',
                        data: JSON.parse('<?=json_encode(array_values($countOfDepositTxsByMinutes))?>'),
                        backgroundColor: 'rgba(48, 155, 223, 0.6)',
                        borderColor: '#3498db',
                        lineTension: 0.1
                    }
                ]
            },
            options: {
                scales: {
                    yAxes: [{ticks: {suggestedMin: 0, stepSize: <?=$stepDeposit?>}}]
                }
            }
        });
    </script>

<?php endif; ?>
<?php if (!empty($countOfWithdrawTxsByMinutes)): ?>
    <script>
        new Chart(document.getElementById("txs-by-minutes-withdraw").getContext('2d'), {
            type: 'line',
            data: {
                labels: JSON.parse('<?=json_encode(array_keys($countOfWithdrawTxsByMinutes))?>'),
                datasets: [
                    {
                        label: 'Withdraw',
                        data: JSON.parse('<?=json_encode(array_values($countOfWithdrawTxsByMinutes))?>'),
                        backgroundColor: 'rgba(232, 76, 56, 0.6)',
                        borderColor: '#e74c3c',
                        lineTension: 0.1
                    }
                ]
            },
            options: {
                scales: {
                    yAxes: [{ticks: {suggestedMin: 0, stepSize: <?=$stepWithdraw?>}}]
                }
            }
        });
    </script>
<?php endif;