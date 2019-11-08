import $ from 'jquery'

export default JSON.parse($('script[data-type="module-data"]').text().trim());
