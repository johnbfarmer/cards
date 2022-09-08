var axios = require('axios')

const apiMap = {}
const urlPrefix = ''

const getData = (view, id) => {
    view = apiMap[view] || view
    var url = urlPrefix + view
    if (id) {
        url = url + id
    }

    return axios
        .get(url)
        .then(function(response) {
            return response
        })
        .catch(function(error) {
            console.log('error::', error)
        })
}

const postData = (view, data, id) => {
    view = apiMap[view] || view
    var url = urlPrefix + view
    if (id) {
        url = url + id
    }

    return axios
        .post(url, data)
        .then(function(response) {
            return response
        })
        .catch(function(error) {
            console.log('error::', error)
        })
}

const saveForm = (view, data) => {
    var url = urlPrefix + '/api/save/' + view
    return axios
        .post(url, data)
        .then(function(response) {
            return response
        })
        .catch(function(error) {
            console.log('error::', error)
        })
}

const deleteRecord = (view, id) => {
    var url = urlPrefix + '/api/delete/' + view + '/' + id

    return axios
        .get(url)
        .then(function(response) {
            return response
        })
        .catch(function(error) {
            console.log('error::', error)
        })
}

module.exports = {
    getData,
    postData,
    saveForm,
    deleteRecord,
    urlPrefix
}