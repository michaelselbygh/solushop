<template>
    <div>
        <h5 class="card-title">
            <div class="row">
                <div class="col-md-7">
                    Edit {{ partner.first_name }}'s details 
                </div>
                <div class="col-md-5" style="text-align: right;">
                    Partner ID: <b>{{ partner.id }}</b>
                </div>
            </div>
        </h5>
         <div class="card" style="">
             <transition name="fade" mode="out-in">
                <div  v-if="loading.details"  class="div-preloader-wrap">
                    <div class=" custom-center">
                        <img  style="" src="https://www.solushop.com.gh/app/assets/img/loader.gif" alt="" > 
                    </div>
                </div>
            </transition>
            <div class="card-content collapse show">
                <div class="card-body">
                    <div class="form-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="first_name">First Name</label>
                                    <input class="form-control round" placeholder="Enter first name" v-model="partner.first_name" type="text" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input class="form-control round" placeholder="Enter last name" v-model="partner.last_name"  type="text" required> 
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="dp_company">Company</label>
                                    <input class="form-control round" v-model="partner.dp_company"  type="text" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input class="form-control round" placeholder="Enter email" v-model="partner.email"  type="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="payment_details">Payment Details</label>
                            <input class="form-control round" placeholder="Enter Payment Details" v-model="partner.payment_details" type="text" required>
                        </div>
                    </div>
                    <div class="form-actions" style="text-align:center; padding: 0px;">
                        <button @click.prevent="triggerDetailsUpdate()" class="btn btn-success">
                                Update {{ partner.first_name }}'s Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['loading', 'partner'],
     methods: {
        triggerDetailsUpdate(){
            this.$emit("updateDetails", "")
        }
    }
}
</script>