/**
 * Module-level registry that StepReview populates when mounted.
 * Allows any component (e.g. DevToolbar) to trigger the booking
 * submission without being coupled to StepReview's internals.
 */
export const submitRegistry = { submit: null };
