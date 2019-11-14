import $ from 'jquery'
import { trim } from 'lodash'

export default JSON.parse(trim($('script[data-type="module-data"]').text()));
