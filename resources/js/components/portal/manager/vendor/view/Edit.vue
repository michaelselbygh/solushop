<template>
    <div>
        <h5 class="card-title">
            <div class="row">
                <div class="col-md-7">
                    Edit {{ vendor.name }}'s details 
                </div>
                <div class="col-md-5" style="text-align: right;">
                    Vendor ID: <b>{{ vendor.id }}</b>
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
                                    <label for="name">Name</label>
                                    <input v-model="vendor.name" class="form-control round" placeholder="Enter Vendor name" type="text"/>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input v-model="vendor.email"  class="form-control round" placeholder="Enter email" type="email" > 
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username</label>
                                    <input v-model="vendor.username" class="form-control round" type="text" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pin">PIN</label>
                                    <input v-model="vendor.passcode"  class="form-control round" type="text" readonly> 
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="main_phone">Main Phone Number</label>
                                    <input v-model="vendor.phone"  class="form-control round" placeholder="Enter main phone e.g. 233204456789" type="text" >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="alt_phone">Alternate Phone Number</label>
                                    <input v-model="vendor.alt_phone" class="form-control round" placeholder="Enter alternate phone e.g. 233204456789" type="text" >
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="mode_of_payment">Mode of Payment</label>
                                    <fieldset class="form-group" >
                                        <select class="form-control" v-model="vendor.mode_of_payment" id="mode_of_payment" style='border-radius:7px;' >
                                            <option>MTN Mobile Money</option>
                                            <option>Vodafone Cash</option>
                                            <option>AirtelTigo Money</option>
                                            <option>Bank Account</option>
                                        </select>
                                    </fieldset>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_details">Payment Details</label>
                                    <input v-model="vendor.payment_details"  class="form-control round" placeholder="Enter payment details" type="text" >
                                </div>
                            </div>
                        </div>
                         <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="header">Current Header Image</label>
                                    <a target="_blank" :href="'https://www.solushop.com.gh/app/assets/img/vendor-banner/'+ vendor.id +'.jpg'">
                                        <input id="header" style="cursor:pointer" name="header"  class="form-control round" :value="vendor.id+'.jpg'" type="text" readonly>
                                    </a>
                                    </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="header_image">Update Header Image</label>
                                    <input type="file" class="form-control-file" @change="handleFileUpload()" ref="header" id="header">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="pick_up_address">Pick Up Address</label>
                            <input v-model="vendor.address"  class="form-control round" placeholder="Enter Pick-Up Address" type="text" >
                        </div>
                        <div class="form-group">
                            <label for="shop">Shop URL</label>
                            <input id="shop" name="shop"  class="form-control round" :value="'https://www.solushop.com.gh/shop/' + vendor.username" type="text" readonly>
                        </div>
                    </div>
                    <div class="form-actions" style="text-align:center; padding: 0px;">
                        <button @click.prevent="triggerDetailsUpdate()" class="btn btn-success">
                                Update {{ vendor.name }}'s Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['loading', 'vendor'],
     methods: {
        triggerDetailsUpdate(){
            this.$emit("updateDetails", "")
        },

        handleFileUpload(){
            this.vendor.header = this.$refs.header.files[0];
        }
    }
}
</script>
