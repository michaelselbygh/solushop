<template>
    <div>
        <h5 style="text-align: right">Balance : <b style="color: green">GH¢ {{ partner.balance.toFixed(2) }}</b></h5>
        <div class="card">
            <transition name="fade" mode="out-in">
                <div  v-if="loading.pay"  class="div-preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
            </transition>
            <div class="card-content collapse show">
                <div class="card-body">
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-sm-3">
                        </div>
                        <div class="col-sm-6" style="text-align: center;"> 
                            <div class="form-group" style="display: inline-block">
                                <input type="hidden" name="sa_action" value="record_payout"/>
                                <input class="form-control round" placeholder="0.00" v-model="pay.amount" type="number" :max="partner.balance" step="0.1" style="width: 100%;" required><br>
                                <button @click="triggerPartnerPayment()" type="submit" name="record_payout" class="btn btn-success">
                                        Record Payout
                                </button> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['partner', 'loading', 'pay'],
    methods: {
        triggerPartnerPayment(){
            this.$emit("payPartner", "");
        }
    }
}
</script>