export default class RequestError extends Error {
  constructor(response) {
    super(response.message);
    this.errors = response.errors;
    this.status = response.status;
  }
}
