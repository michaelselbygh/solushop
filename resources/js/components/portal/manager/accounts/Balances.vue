<template>
    <div>
        <h5 class="card-title">Balance</h5>
        <div class="card">
            <div class="card-content collapse show">
                <div class="card-body card-dashboard">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Total : </h6>
                        </div>
                        <div class="col-md-6" >
                            <h6><b>GH¢ {{ balances.total.toFixed(2) }}</b></h6>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Due Vendors : </h6>
                        </div>
                        <div class="col-md-6" >
                            <h6 style="color: #fbae17;"><b>GH¢ {{ balances.vendors.toFixed(2) }}</b></h6>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Due Sales Associates : </h6>
                        </div>
                        <div class="col-md-6" >
                            <h6 style="color: #fbae17;"><b>GH¢ {{ balances.sales_associates.toFixed(2) }}</b></h6>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Due Delivery Partners : </h6>
                        </div>
                        <div class="col-md-6" >
                            <h6 style="color: #fbae17;"><b>GH¢ {{ balances.delivery_partners.toFixed(2) }}</b></h6>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Available : </h6>
                        </div>
                        <div class="col-md-6" >
                            <h6 style="color: green;"><b>GH¢ {{ balances.available.toFixed(2) }}</b></h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    props: ['balances']
}
</script>
