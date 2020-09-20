<template>
    <div>
        <h5 class="card-title">
            <div class="row">
                <div class="col-md-7">
                    Edit {{ associate.first_name }}'s details 
                </div>
                <div class="col-md-5" style="text-align: right;">
                    Associate ID: <b>{{ associate.id }}</b>
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
                                    <input v-model="associate.first_name" class="form-control round" placeholder="Enter first name" type="text" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="last_name">Last Name</label>
                                    <input v-model="associate.last_name"  class="form-control round" placeholder="Enter last name" type="text" required> 
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input v-model="associate.email"  class="form-control round" placeholder="Enter email" type="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">PIN</label>
                                    <input v-model="associate.passcode"  class="form-control round" type="text" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input v-model="associate.phone" class="form-control round" placeholder="Enter phone e.g. 0204456789" type="text" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="identification">Identification</label>
                                    <a target="_blank" :href="'https://www.solushop.com.gh/portal/s-team-member-id/' + associate.id_file ">
                                        <input v-model="associate.id_file" style="cursor:pointer" class="form-control round"  type="text" readonly>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mode_of_payment">Mode of Payment</label>
                                    <fieldset class="form-group" >
                                        <select class="form-control" v-model='associate.mode_of_payment' id="mode_of_payment" style='border-radius:7px;' required>
                                            <option>MTN Mobile Money</option>
                                            <option>Vodafone Cash</option>
                                            <option>Bank Account</option>
                                        </select>
                                    </fieldset>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_details">Payment Details</label>
                                    <input v-model="associate.payment_details"  class="form-control round" placeholder="Enter payment details" type="text" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="residential_address">Residential Address</label>
                            <input v-model="associate.address"  class="form-control round" placeholder="Enter Residential Address" type="text" required>
                        </div>
                    </div>
                    <div class="form-actions" style="text-align:center; padding: 0px;">
                        <button @click.prevent="triggerDetailsUpdate()" class="btn btn-success">
                                Update {{ associate.first_name }}'s Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['loading', 'associate'],
     methods: {
        triggerDetailsUpdate(){
            this.$emit("updateDetails", "")
        }
    }
}
</script>