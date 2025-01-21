import axios from 'axios'

export async function exportData(format = 'xlsx', filename = 'export', columns = null, filters = {}, styling = {}) {
  try {
    const response = await axios.get('/api/export', {
      params: { format, filename, columns, filters, styling },
      responseType: 'blob'
    });

    // Create a download link for the exported file
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `${filename}.${format}`);
    document.body.appendChild(link);
    link.click();
    link.remove();
  } catch (error) {
    console.error('Export failed:', error);
  }
}
