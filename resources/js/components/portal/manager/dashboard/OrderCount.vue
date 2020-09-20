<template>
    <div class="col-xl-3 col-lg-6 col-12">
        <div class="card pull-up" style="cursor:pointer;" @click="triggerFilterUpdate(label)">
            <div class="card-content">
                <div class="card-body">
                    <div class="media d-flex">
                        <div class="media-body text-left">
                            <h3 :class="cc"><b>{{ order.count }}</b></h3>
                            <h6>{{ label }} Orders</h6>
                        </div>
                        <div>
                            <i class="icon-plus info font-large-2 float-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: ['state', 'cc', 'label'],
    data() {
        return {
            order: {
                count : 0,
            }
        }
    },    
    methods: {
        triggerFilterUpdate(filter){
            this.$emit('clicked', filter)
        }
    }
}
</script>