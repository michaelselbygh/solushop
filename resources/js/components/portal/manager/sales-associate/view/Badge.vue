<template>
    <div>        
        <h5 style="text-align: right">Badge &amp; Sales</h5>
        <div class="card">
            <div class="card-content collapse show">
                <div class="card-body" style="padding-top: 5px; padding-bottom: 5px;">
                    <div class="row">
                        <div class="col-sm-12">
                            <div style="text-align: center">
                                <img style="width: 140px;" :src="'https://www.solushop.com.gh/portal/images/s-team-badges/' + associate.badge_info.sab_image"/>
                            </div>
                            <div>Code : <b>{{ associate.id_file.substr(0, 24) }}</b></div>
                            <div>Status : <b>{{ associate.badge_info.sab_description }}</b></div>
                            <div>Commission : <b>{{ associate.badge_info.sab_commission * 100 }} %</b></div>
                            <div>Total Sales : <b>GH¢ {{ associate.sales.toFixed(2) }}</b></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['associate']
}
</script>