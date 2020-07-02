# Distributed workers

The main distribution happens with the help of a beanstalkd queue.

The application has two components:
### Distributor
This process is responsible for distributing the tasks between the workers.
By its nature it can only be run exclusively.
### Worker
The workers are pulling tasks of the queue and processing them one-by-one.