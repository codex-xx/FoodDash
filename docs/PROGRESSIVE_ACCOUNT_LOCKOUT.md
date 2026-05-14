# Progressive Account Lockout Security - Implementation Guide

## Overview

Your FoodDash application now features a **progressive account lockout security system** that protects user accounts from brute-force attacks through intelligent, escalating penalties.

---

## How Progressive Lockout Works

### The System

When a user enters an incorrect password 3 times, their account is temporarily locked. Each time they unlock and fail again, the lock duration increases progressively:

```
User Login Attempt Flow:
├─ Attempt 1 (fail)      → Error: "Invalid password. 2 attempts remaining"
├─ Attempt 2 (fail)      → Error: "Invalid password. 1 attempt remaining"
├─ Attempt 3 (fail)      → Account LOCKED for 1 minute
│                           Error: "Account locked for 1 minute(s)"
├─ (Try during lock)     → Error: "Too many failed attempts. Please wait X minute(s)"
│
├─ [1 minute passes, account auto-unlocks]
│
├─ Attempt 4 (fail)      → Error: "Invalid password. 2 attempts remaining"
├─ Attempt 5 (fail)      → Error: "Invalid password. 1 attempt remaining"
├─ Attempt 6 (fail)      → Account LOCKED for 5 minutes (2nd lock = 5 min)
│
├─ [5 minutes pass, account auto-unlocks]
│
├─ Attempt 7 (fail)      → Error: "Invalid password. 2 attempts remaining"
├─ Attempt 8 (fail)      → Error: "Invalid password. 1 attempt remaining"
├─ Attempt 9 (fail)      → Account LOCKED for 15 minutes (3rd lock = 15 min)
│
├─ [15 minutes pass, account auto-unlocks]
│
└─ Attempt 10+ (fail)    → Account LOCKED for 30 minutes (4th+ lock = 30 min)
```

### Progressive Lock Durations

| Lock Cycle | Duration | After Failed Attempts |
|------------|----------|----------------------|
| 1st lock | 1 minute | 3, 6, 9 attempts |
| 2nd lock | 5 minutes | 6, 9 attempts |
| 3rd lock | 15 minutes | 9 attempts |
| 4th+ lock | 30 minutes | 12+ attempts |

### Key Features

✅ **Account-Level Protection**
- Locks are per-user account, not IP-based
- Legitimate users from different IPs unaffected
- Automatic unlock after timer expires

✅ **Progressive Penalties**
- Gets stricter with repeated violations
- Prevents attacker from continuously trying
- Gives attacker less time between retry cycles

✅ **User-Friendly**
- Users see remaining attempts before lockout
- Users see countdown timer while locked
- Lock auto-expires (no manual admin intervention needed for most cases)

✅ **Admin Friendly**
- Simple database fields (`failed_attempts`, `locked_until`, `lock_count`)
- No complex IP tracking required
- Easy to manually unlock a legitimate user if needed

---

## Database Schema

### Required User Table Columns

```sql
ALTER TABLE users ADD COLUMN failed_attempts INT DEFAULT 0;
ALTER TABLE users ADD COLUMN locked_until DATETIME NULL;
ALTER TABLE users ADD COLUMN lock_count INT DEFAULT 0;
```

### Column Descriptions

| Column | Purpose | Reset When |
|--------|---------|-----------|
| `failed_attempts` | Current failed attempt count (0-3) | Lock expires OR successful login |
| `locked_until` | When lock expires (NULL if not locked) | Lock expires OR admin reset |
| `lock_count` | Total times locked (tracks progression) | Manual admin reset only |

---

## User Experience Examples

### Example 1: Normal Login Attempt
```
User enters email: john@example.com
User enters password: mypassword123

System checks: ✓ Email exists, ✓ Password valid
User logged in successfully
All counters reset: failed_attempts=0, lock_count stays for future reference
```

### Example 2: Forgot Password (3 Wrong Attempts)
```
Attempt 1: "Invalid password. 2 attempts remaining"
Attempt 2: "Invalid password. 1 attempt remaining"
Attempt 3: "Too many failed login attempts. Account locked for 1 minute(s). Please try again later."

[After 1 minute auto-unlock]
Attempt 4: User can try again, counter resets to 0
```

### Example 3: Persistent Attacker (Multiple Lockout Cycles)
```
Lockout 1: 3 failed → locked 1 minute
Lockout 2: 3 more failed → locked 5 minutes
Lockout 3: 3 more failed → locked 15 minutes
Lockout 4: 3 more failed → locked 30 minutes ← Strong deterrent
```

---

## Admin Tasks

### Checking if User is Locked

**SQL Query:**
```sql
SELECT email, failed_attempts, locked_until, lock_count 
FROM users 
WHERE locked_until IS NOT NULL AND locked_until > NOW();
```

### Manually Unlock a User

**For legitimate user who forgot password:**
```sql
UPDATE users 
SET failed_attempts = 0, locked_until = NULL, lock_count = 0 
WHERE email = 'user@example.com';
```

**Alternative (keep lock_count for audit trail):**
```sql
UPDATE users 
SET failed_attempts = 0, locked_until = NULL 
WHERE email = 'user@example.com';
```

### View Lock Statistics

**Users currently locked:**
```sql
SELECT email, locked_until, 
       TIMESTAMPDIFF(MINUTE, NOW(), locked_until) as remaining_minutes,
       lock_count 
FROM users 
WHERE locked_until IS NOT NULL AND locked_until > NOW()
ORDER BY locked_until DESC;
```

**Users with high lock counts (repeat violators):**
```sql
SELECT email, lock_count, created_at, last_login 
FROM users 
WHERE lock_count >= 3
ORDER BY lock_count DESC;
```

---

## Code Implementation Details

### Location: `app/Controllers/Auth.php`

#### Progressive Lock Minutes Calculation
```php
protected function resolveProgressiveLockMinutes(int $lockCount): int
{
    return match($lockCount) {
        1 => 1,     // 1st lock = 1 minute
        2 => 5,     // 2nd lock = 5 minutes
        3 => 15,    // 3rd lock = 15 minutes
        default => 30,  // 4th+ lock = 30 minutes
    };
}
```

#### Login Attempt Process
```php
public function attempt()
{
    // 1. Validate input (email, password required)
    
    // 2. Check if user exists
    // If not: return "Invalid email or password"
    
    // 3. Check if account is locked
    if (account_is_locked && lock_not_expired) {
        return "Account locked for X minute(s)"
    }
    if (lock_expired) {
        reset_failed_attempts()
        reset_lock_time()
    }
    
    // 4. Verify password
    if (password_wrong) {
        increment_failed_attempts()
        if (failed_attempts >= 3) {
            lock_account_with_progressive_duration()
            increment_lock_count()
        }
        return "Invalid password. X attempts remaining"
    }
    
    // 5. Successful login
    reset_failed_attempts()
    reset_lock_time()
    create_session()
    return redirect_to_dashboard()
}
```

---

## Security Benefits

### Against Brute-Force Attacks
✅ After 3 failed attempts, attacker must wait 1 minute
✅ If they return and fail 3 more times, they wait 5 minutes
✅ Repeated failures result in 30-minute lockouts
✅ Makes brute-forcing impractical (3 attempts × 5+ lockout cycles = hours of waiting)

### Against Credential Stuffing
✅ Even if attacker has username, they need the correct password in 3 tries
✅ Progressive lockouts make bulk testing ineffective
✅ Each failed password attempt triggers longer waiting periods

### Against Account Enumeration
✅ Different error messages don't leak account existence
✅ All failed attempts ("Invalid email or password") treat user and password as one unit

### Resource Efficient
✅ No complex IP tracking needed
✅ No distributed cache required
✅ Simple database columns only
✅ Minimal server overhead

---

## Configuration & Customization

### Current Settings (Recommended)

Located in `app/Controllers/Auth.php`:

```php
$maxAttempts = 3;  // 3 failed attempts trigger lock
```

To change lock durations, edit `resolveProgressiveLockMinutes()` method:

```php
protected function resolveProgressiveLockMinutes(int $lockCount): int
{
    return match($lockCount) {
        1 => 2,     // Change to 2 minutes
        2 => 10,    // Change to 10 minutes
        3 => 20,    // Change to 20 minutes
        default => 60,  // Change to 1 hour
    };
}
```

### Alternative Configurations

**Stricter Security:**
```php
// Trigger lock at 2 failed attempts
$maxAttempts = 2;

// Longer lock durations
1 => 5,      // 5 minutes
2 => 15,     // 15 minutes
3 => 30,     // 30 minutes
default => 60,  // 1 hour
```

**More User-Friendly:**
```php
// Allow 4 attempts before lock
$maxAttempts = 4;

// Shorter lock durations
1 => 1,      // 1 minute
2 => 3,      // 3 minutes
3 => 10,     // 10 minutes
default => 30,  // 30 minutes
```

---

## Comparison: Progressive Lockout vs Other Approaches

### Progressive Account Lockout (Current)
```
Pros:
  ✓ Simple to understand
  ✓ Protects all users fairly
  ✓ No geographic issues
  ✓ Auto-recovers after timeout
  ✓ Minimal database overhead
  ✓ Works across all networks

Cons:
  ✗ Cannot block truly automated bulk attacks
  ✗ Legitimate users occasionally inconvenienced
```

### IP-Based Blocking
```
Pros:
  ✓ Stops bulk attacks from single IP
  ✓ No legitimate user lockout

Cons:
  ✗ Blocks legitimate users behind same IP/NAT
  ✗ Office networks, school networks affected
  ✗ Complex to manage whitelist
  ✗ Increased false positives
```

### Combined Approach (Not Used Here)
```
Pros:
  ✓ Maximum protection

Cons:
  ✗ Very complex implementation
  ✗ Maintenance overhead
  ✗ More false positives
  ✗ Privacy concerns
```

---

## Testing the Feature

### Test 1: Account Lockout
```bash
1. Go to login page
2. Enter email: admin@example.com
3. Enter wrong password 3 times
4. Observe: "Account locked for 1 minute(s)"
5. Try to login immediately
6. Observe: "Too many failed attempts. Please wait 1 minute(s)"
7. Wait 61 seconds, try again
8. Observe: Can now attempt login (counter reset)
```

### Test 2: Progressive Lockout
```bash
1. Lock account 1st time (1 min lock)
2. Wait for unlock
3. Fail login 3 times again
4. Observe: "Account locked for 5 minute(s)" ← Progression!
5. Wait for unlock
6. Fail login 3 times again
7. Observe: "Account locked for 15 minute(s)" ← Further progression!
```

### Test 3: Successful Login Resets
```bash
1. Fail login 1 time
2. Fail login 2 times
3. Login successfully with correct password
4. Fail login 1 time (should show "2 attempts remaining", not "1")
5. Verify counter reset to 0
```

---

## Support & Troubleshooting

### User Can't Login (Locked Account)

**Question:** How long is the lockout?
**Answer:** It depends on how many times they've been locked:
- 1st time: 1 minute
- 2nd time: 5 minutes
- 3rd time: 15 minutes
- 4th+ time: 30 minutes

**Resolution:** Tell user to wait, or admin can reset with:
```sql
UPDATE users SET failed_attempts=0, locked_until=NULL WHERE email='...';
```

### I Want to Change Lock Durations

**Edit:** `app/Controllers/Auth.php`
**Find:** `resolveProgressiveLockMinutes()`
**Modify:** The match array values
**Save:** Changes apply to next login attempt

### I Want to Allow More Failed Attempts

**Edit:** `app/Controllers/Auth.php`
**Find:** `$maxAttempts = 3;`
**Change to:** `$maxAttempts = 4;` (or higher)
**Note:** This is inside the `attempt()` method

### User Reports Being Locked Incorrectly

**Check:** 
```sql
SELECT email, failed_attempts, locked_until 
FROM users 
WHERE email = 'user@example.com';
```

**Reset if needed:**
```sql
UPDATE users 
SET failed_attempts=0, locked_until=NULL, lock_count=0 
WHERE email='user@example.com';
```

---

## Conclusion

Progressive account lockout provides excellent brute-force protection with minimal complexity and false positives. The system is:

- ✅ **Simple** - Easy to understand and maintain
- ✅ **Effective** - Deters attackers progressively
- ✅ **Fair** - Doesn't penalize legitimate users behind NAT/VPN
- ✅ **Automatic** - No manual intervention needed for unlocks
- ✅ **Scalable** - Works for any number of users

For questions or customizations, refer to the configuration section or contact the development team.
