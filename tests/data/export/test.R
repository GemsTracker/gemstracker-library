data <- read.csv("test.csv", quote="'\"", stringsAsFactors=FALSE)

data[,1] <- as.character(data[,1])
data[,2] <- as.numeric(data[,2])
data[,3] <- as.character(data[,3])

#Define variable labels.
attributes(data)$variable.labels[1] <- "Enter some text"
attributes(data)$variable.labels[2] <- "Choose one"
attributes(data)$variable.labels[3] <- "Choose something"

#Define value labels.
data[,2] <- factor(data[,2], levels=c(1,2), labels=c("Yes","No"))
data[,3] <- factor(data[,3], levels=c("1","a"), labels=c("Yes","No"))
