<template>
    @foreach ($detail_data as $detail_datum)
        <el-row>
            <el-col :span="4">
                <label for="{{$detail_datum}}">{{$detail_datum}}</label>
            </el-col>
            <el-col :span="16">
                <el-input id="{{$detail_datum}}"
                          :class="{aggravation:detail_data.{{$detail_datum}}}"
                          v-model="detail_data.{{$detail_datum}}"
                          :disabled="is_disabled_edit"
                          placeholder="{{$detail_datum}}"></el-input>
            </el-col>
        </el-row>
    @endforeach
</template>