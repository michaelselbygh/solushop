<template>
    <div>
        <h5 class="card-title">{{ partner.first_name }}'s Transaction History</h5>
        <div class="card">
            <transition name="fade" mode="out-in">
                <div  v-if="loading.transactions"  class="div-preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
            </transition>
            <div class="card-content collapse show">
                <div class="card-body card-dashboard">
                    <table ref="transactions" class="table table-striped table-bordered zero-configuration" id="transaction-records">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Deb.</th>
                                <th>Cred.</th>
                                <th style="min-width: 80px;"></th>
                                <th style="min-width: 250px;">Description</th>
                                <th>Date</th>
                                <th>Recorder</th>
                            </tr>
                        </thead>
                        <tbody> 
                            <tr v-for="transaction in transactions" :key="transaction.id">
                                <td> {{ transaction.id }} </td>
                                <td> {{transaction.trans_credit_account }} </td>
                                <td> {{transaction.trans_debit_account }} </td>
                                <td>
                                    {{ "GH¢ " + transaction.trans_amount }}
                                    <img :src="arrow(transaction.trans_debit_account_type, transaction.trans_credit_account_type)" style="width: 30px;"/>
                                </td>
                                <td>{{ transaction.trans_description }}</td>
                                <td>{{ transaction.trans_date.substr(0, 10) }}</td>
                                <td>{{ transaction.trans_recorder }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ["transactions", "loading", "partner"],
    mounted() {
        this.dt = $(this.$refs.transactions).DataTable({
                    "order": [
                        [0, 'desc']
                    ],
                    "drawCallback": function() {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                });
        $('[data-toggle="tooltip"]').tooltip();
    },
    watch: {
        transactions(val) {
            this.dt.destroy();
            this.$nextTick(() => {
                this.dt = $(this.$refs.transactions).DataTable({
                    "order": [
                        [0, 'desc']
                    ],
                    "drawCallback": function() {
                        $('[data-toggle="tooltip"]').tooltip();
                    },
                });
                $('[data-toggle="tooltip"]').tooltip();
            });
        }
    },
    methods: {
        arrow(debit, credit){
            if (debit == 1 &&  [2, 4, 6, 8, 10].includes(credit)) {
                return "https://www.solushop.com.gh/portal/images/transactions/green-in.png"
            } else if (credit == 1 &&  [2, 4, 6, 8, 10].includes(debit)) {
                return "https://www.solushop.com.gh/portal/images/transactions/red-out.png"
            } else if (debit == 1 &&  [3, 5, 7, 9].includes(credit)) {
                return "https://www.solushop.com.gh/portal/images/transactions/yellow-in.png"
            } else if (credit == 1 &&  [3, 5, 7, 9].includes(debit)) {
                return "https://www.solushop.com.gh/portal/images/transactions/yellow-out.png"
            } else {
                return "https://www.solushop.com.gh/portal/images/transactions/neutral.png"
            }
        }
    }
}
</script>