---
layout: page
title: "Filter Methods"
category: fil
date: 2016-10-18 15:14:37
---

Filter methods store the query elements below, and can be applied to a Query when the Filter is used.

### where
 
| Parameters   | Array $where    |
|              | $innercondition = 'AND' |
|              | $outercondition = 'AND' |
| Returns      | $this | 

This is an analogue for the Blueprnt->where() method, and works in the same way.

---

### orderBy
 
| Parameters   | $columnname_or_array  |
|              | $order = 'ASC'  |
| Returns      | $this |

This is an analogue for the Blueprnt->orderBy() method, and works in the same way.

---

### limit
 
| Parameters   | $limit  |
|              | $offset = false  |
| Returns      | $this |

This is an analogue for the Blueprnt->limit() method, and works in the same way.

---

### setQueryParams
 
| Parameters   | QueryInterface $query  |
| Returns      | $query |

This function causes the Filter's where, orderBy and limit settings to be applied to the $query.

This is normally only used in the main Blueprint class and can be ignored.

---

