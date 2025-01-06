<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  userId: {
    type: Number,
    required: true
  }
})

const exportReport = async (format) => {
  try {
    const response = await axios.get(`/api/productivity/export/${props.userId}?format=${format}`, {
      responseType: 'blob'
    });
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `productivity_report.${format}`);
    document.body.appendChild(link);
    link.click();
  } catch (error) {
    console.error('Error exporting report:', error);
  }
}
</script>

<template>
  <div>
    <button @click="exportReport('pdf')">Export as PDF</button>
    <button @click="exportReport('excel')">Export as Excel</button>
  </div>
</template>
