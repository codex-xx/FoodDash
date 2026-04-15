package com.fooddash.models;

/**
 * Order Status Constants for FoodDash Mobile App
 * Represents the delivery workflow without manual driver assignment
 */
public class OrderStatus {
    public static final String STATUS_PENDING = "pending";
    public static final String STATUS_ACCEPTED = "accepted";
    public static final String STATUS_PREPARING = "preparing";
    public static final String STATUS_READY = "ready";
    public static final String STATUS_PICKED_UP = "picked_up";
    public static final String STATUS_ARRIVED_RESTAURANT = "arrived_at_restaurant";
    public static final String STATUS_OUT_FOR_DELIVERY = "out_for_delivery";
    public static final String STATUS_DELIVERED = "delivered";
    public static final String STATUS_CANCELLED = "cancelled";

    /**
     * Get all valid order statuses
     */
    public static String[] getAllStatuses() {
        return new String[] {
            STATUS_PENDING,
            STATUS_ACCEPTED,
            STATUS_PREPARING,
            STATUS_READY,
            STATUS_PICKED_UP,
            STATUS_ARRIVED_RESTAURANT,
            STATUS_OUT_FOR_DELIVERY,
            STATUS_DELIVERED,
            STATUS_CANCELLED
        };
    }

    /**
     * Check if a status is valid
     */
    public static boolean isValid(String status) {
        for (String validStatus : getAllStatuses()) {
            if (validStatus.equals(status)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get allowed statuses for a specific user role
     */
    public static String[] getAllowedStatusesForRole(String role) {
        return switch (role) {
            case "restaurant" -> new String[] {
                STATUS_ACCEPTED,
                STATUS_PREPARING,
                STATUS_READY
            };
            case "driver" -> new String[] {
                STATUS_PICKED_UP,
                STATUS_ARRIVED_RESTAURANT,
                STATUS_OUT_FOR_DELIVERY,
                STATUS_DELIVERED
            };
            case "customer" -> new String[] {
                STATUS_CANCELLED
            };
            default -> getAllStatuses();
        };
    }

    /**
     * Order Status Workflow:
     * 
     * CUSTOMER CREATES ORDER:
     * pending -> RESTAURANT accepts -> accepted
     * 
     * RESTAURANT PREPARES:
     * accepted -> restaurant starts prep -> preparing
     * preparing -> food is ready -> ready
     * 
     * DRIVER ACCEPTS & DELIVERS:
     * ready -> driver picks up order -> picked_up
     * picked_up -> driver arrives at restaurant -> arrived_at_restaurant
     * arrived_at_restaurant -> driver leaves to deliver -> out_for_delivery
     * out_for_delivery -> driver delivers -> delivered
     * 
     * CANCELLATION:
     * Any status before picked_up -> CANCELLED
     */
}
